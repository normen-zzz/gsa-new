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
            'awb.pod',
            'pod.name_airport as pod_name',
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
    

    
}
