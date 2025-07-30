<?php

namespace App\Models\flow;

use Exception;

use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;


class JobModel extends Model
{
    public function getJob($search = '', $limit = 10)

    {
        // Optimasi query untuk kinerja yang lebih baik
        $select = [
            'job.id_job',
            'job.agent',
            'agent.name_customer as agent_name',
            'job.agent as agent_data',
            'job.consignee',
            'awb.id_awb',
            'awb.awb',
            'awb.etd',
            'awb.eta',
            'awb.pol',
            'pol.name_airport as pol_name',
            'pol.code_airport as pol_code',
            'awb.pod',
            'pod.name_airport as pod_name',
            'pod.code_airport as pod_code',
            'awb.commodity',
            'awb.weight',
            'awb.pieces',
            'awb.dimensions',
            'awb.data_flight',
            'awb.handling_instructions',
            'job.status',
            'job.created_at',


        ];
        $job = DB::table('job')
            ->join('awb', 'job.id_awb', '=', 'awb.id_awb')
            ->join('customers as agent', 'job.agent', '=', 'agent.id_customer')
            
            ->join('airports as pol', 'awb.pol', '=', 'pol.id_airport')
            ->join('airports as pod', 'awb.pod', '=', 'pod.id_airport')
            // Menggunakan when() untuk kondisi pencarian hanya jika ada
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('awb.awb', 'like', '%' . $search . '%')
                        ->orWhere('agent.name_customer', 'like', '%' . $search . '%')
                        ->orWhere('consignee.name_customer', 'like', '%' . $search . '%')
                        ->orWhere('pol.name_airport', 'like', '%' . $search . '%')
                        ->orWhere('pod.name_airport', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('job.id_job', 'desc')
            ->select(
                $select
            )
            ->paginate($limit);

        // Mengoptimalkan proses decode JSON dengan transform collection
        $job->getCollection()->transform(function ($j) {
            if (!empty($j->data_flight)) {
                $j->data_flight = json_decode($j->data_flight, true);
            }
            if (!empty($j->dimensions)) {
                $j->dimensions = json_decode($j->dimensions, true);
            }

            $agentData = DB::table('data_customer')
                ->where('id_customer', $j->agent_data)
                ->first();

            $j->agent_data = $agentData ? json_decode($agentData->data, true) : [];
            return $j;
        });

        return $job;
    }

    public function getJobById($id)
    {
        $select = [
            'job.id_job',
            'job.agent',
            'agent.name_customer as agent_name',
            'job.agent as agent_data',
            'job.consignee',
            'awb.id_awb',
            'awb.awb',
            'awb.etd',
            'awb.eta',
            'awb.pol',
            'pol.name_airport as pol_name',
            'awb.pod',
            'pod.name_airport as pod_name',
            'awb.commodity',
            'awb.weight',
            'awb.pieces',
            'awb.dimensions',
            'awb.data_flight',
            'awb.handling_instructions',
            'job.status',
            'job.created_at'
        ];

        $job = DB::table('job')
            ->join('awb', 'job.id_awb', '=', 'awb.id_awb')
            ->join('customers as agent', 'job.agent', '=', 'agent.id_customer')
            
            ->join('airports as pol', 'awb.pol', '=', 'pol.id_airport')
            ->join('airports as pod', 'awb.pod', '=', 'pod.id_airport')
            ->where('job.id_job', $id)
            ->select($select)
            ->first();

        if (!$job) {
            return null;
        }

        // Decode JSON fields
        $job->data_flight = json_decode($job->data_flight, true);
        $job->dimensions = json_decode($job->dimensions, true);

        $agentData = DB::table('data_customer')
            ->where('id_customer', $job->agent_data)
            ->first();

        $job->agent_data = $agentData ? json_decode($agentData->data, true) : [];

        return $job;
    }

    public function updateJob (Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'id_job' => 'required|integer|exists:job,id_job',
                'awb' => 'required|string|max:50|exists:awb,awb',
                'consignee' => 'nullable|string|max:255',
                'etd' => 'required|date',
                'eta' => 'required|date',
                'commodity' => 'required|string|max:255',
                'weight' => 'required|numeric|min:0',
                'pieces' => 'required|integer|min:1',
                'dimensions' => 'nullable|json',
                'dimensions.*.length' => 'required|numeric|min:0',
                'dimensions.*.width' => 'required|numeric|min:0',
                'dimensions.*.height' => 'required|numeric|min:0',
                'dimensions.*.weight' => 'required|numeric|min:0',
                'special_instructions' => 'nullable|string|max:500',
                'status' => 'required|in:created_by_cs, handled_by_ops, declined_by_ops,deleted',
                'pol' => 'required|integer|exists:airports,id_airport',
                'pod' => 'required|integer|exists:airports,id_airport',
                'data_flight' => 'nullable|array',
                'data_flight.*.flight_number' => 'required|string|max:255',
                'data_flight.*.departure' => 'required|date',
                'data_flight.*.arrival' => 'required|date',

            ]);

            $dataJob = [
                'consignee' => $data['consignee'],
                'etd' => $data['etd'],
                'eta' => $data['eta'],
                'updated_by' => $request->user()->id_user,
            ];

            $dataAwb = [
                'awb' => $data['awb'],
                'etd' => $data['etd'],
                'eta' => $data['eta'],
                'pol' => $data['pol'],
                'pod' => $data['pod'],
                'commodity' => $data['commodity'],
                'weight' => $data['weight'],
                'pieces' => $data['pieces'],
                'dimensions' => json_encode($data['dimensions']),
                'data_flight' => json_encode($data['data_flight']),
                'handling_instructions' => $data['special_instructions'],
                'created_by' => $request->user()->id_user,
                'updated_at' => now(),
            ];  
            $updateJob = DB::table('job')->where('id_job', $data['id_job'])->update($dataJob);
            if ($updateJob) {
                $updateAwb =  DB::table('awb')->where('awb', $data['awb'])->update($dataAwb);
                if ($updateAwb) {
                    DB::commit();
                    return ResponseHelper::success('Job updated successfully.', NULL, 200);
                } else {
                    throw new Exception('Failed to update AWB.');
                }
            } else {
                throw new Exception('Failed to update job.');
            }
        } catch (Exception $th) {
            DB::rollBack();
            return ResponseHelper::error($th);
        }
    }

    
}
