<?php

namespace App\Models\flow;

use Exception;

use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;






class JobModel extends Model
{
    protected $table = 'job';
    protected $primaryKey = 'id_job';
    public $timestamps = true;
    protected $fillable = [
        'id_shippinginstruction',
        'awb',
        'agent',
        'data_agent',
        'consignee',
        'etd',
        'eta',
        'pol',
        'pod',
        'commodity',
        'gross_weight',
        'chargeable_weight',
        'pieces',
        'special_instructions',
        'status',
        'created_by',
        'updated_by'
    ];

    public function getJob($search = '', $limit = 10)

    {
        // Optimasi query untuk kinerja yang lebih baik
        $select = [
            'job.id_job',
            'job.id_shippinginstruction',
            'job.agent',
            'agent.name_customer as agent_name',
            'job.agent as agent_data',
            'job.consignee',
            'job.etd',
            'job.eta',
            'job.pol',
            'pol.name_airport as pol_name',
            'job.pod',
            'pod.name_airport as pod_name',
            'job.commodity',
            'job.gross_weight',
            'job.chargeable_weight',
            'job.pieces',
            'job.special_instructions',
            'job.status',
            'job.created_at',
            'job.updated_at',
            'job.created_by',
            'job.updated_by',
        ];
        $job = DB::table('job')
            ->leftJoin('customers as agent', 'job.agent', '=', 'agent.id_customer')
            ->leftJoin('airports as pol', 'job.pol', '=', 'pol.id_airport')
            ->leftJoin('airports as pod', 'job.pod', '=', 'pod.id_airport')
            // Menggunakan when() untuk kondisi pencarian hanya jika ada
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('agent.name_customer', 'like', '%' . $search . '%')
                        ->orWhere('job.consignee', 'like', '%' . $search . '%')
                        ->orWhere('pol.name_airport', 'like', '%' . $search . '%')
                        ->orWhere('pod.name_airport', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('job.id_job', 'desc')
            ->select(
                $select
            )
            ->paginate($limit);

        $job->getCollection()->transform(function ($item) {
            $dimension_job = DB::table('dimension_job')
                ->where('id_job', $item->id_job)
                ->get();
            if ($dimension_job) {
                $item->dimensions_job = $dimension_job;
            }

            $data_flightjob = DB::table('flight_job')
                ->where('id_job', $item->id_job)
                ->get();
            if ($data_flightjob) {
                $item->data_flightjob = $data_flightjob;
            }

            $shippingInstruction = DB::table('shippinginstruction')
                ->where('id_shippinginstruction', $item->id_shippinginstruction)
                ->first();
            if ($shippingInstruction) {
                $shippingInstruction->dimensions = json_decode($shippingInstruction->dimensions, true);
                $item->data_shippinginstruction = $shippingInstruction;
            }

            $awb = DB::table('awb')
                ->where('id_job', $item->id_job)
                ->first();
            if ($awb) {
                $item->data_awb = $awb;
                $data_awb = $item->data_awb;
                $dimension_awb = DB::table('dimension_awb')
                    ->where('id_awb', $data_awb->id_awb)
                    ->get();
                if ($dimension_awb) {
                    $data_awb->dimensions_awb = $dimension_awb;
                }
                $flight_awb = DB::table('flight_awb')
                    ->where('id_awb', $data_awb->id_awb)
                    ->get();
                if ($flight_awb) {
                    $data_awb->data_flightawb = $flight_awb;
                }
            }
            return $item;
        });

        return $job;
        
        
    }

    public function getJobById($id)
    {
        $job = DB::table('job')
            ->leftJoin('customers as agent', 'job.agent', '=', 'agent.id_customer')
            ->leftJoin('airports as pol', 'job.pol', '=', 'pol.id_airport')
            ->leftJoin('airports as pod', 'job.pod', '=', 'pod.id_airport')
            ->where('job.id_job', $id)
            ->select(
                'job.id_job',
                'job.id_shippinginstruction',
                'job.agent',
                'agent.name_customer as agent_name',
                'job.data_agent',
                'job.consignee',
                'job.etd',
                'job.eta',
                'job.pol',
                'pol.name_airport as pol_name',
                'job.pod',
                'pod.name_airport as pod_name',
                'job.commodity',
                'job.gross_weight',
                'job.chargeable_weight',
                'job.pieces',
                'job.special_instructions',
                'job.status',
                'job.created_at',
                'job.updated_at',
                'job.created_by',
                'job.updated_by'
            )
            ->first();
        if (!$job) {
            throw new Exception('Job not found', 404);
        } else{
            $job->dimensions_job = DB::table('dimension_job')
                ->where('id_job', $job->id_job)
                ->get();

            $job->data_flightjob = DB::table('flight_job')
                ->where('id_job', $job->id_job)
                ->get();

            $shippingInstruction = DB::table('shippinginstruction')
                ->where('id_shippinginstruction', $job->id_shippinginstruction)
                ->first();
            if ($shippingInstruction) {
                $shippingInstruction->dimensions = json_decode($shippingInstruction->dimensions, true);
                $job->data_shippinginstruction = $shippingInstruction;
            }

            $awb = DB::table('awb')
                ->where('id_job', $job->id_job)
                ->first();
            if ($awb) {
                $job->data_awb = $awb;
                $data_awb = $job->data_awb;
                $dimension_awb = DB::table('dimension_awb')
                    ->where('id_awb', $data_awb->id_awb)
                    ->get();
                if ($dimension_awb) {
                    $data_awb->dimensions_awb = $dimension_awb;
                }
                $flight_awb = DB::table('flight_awb')
                    ->where('id_awb', $data_awb->id_awb)
                    ->get();
                if ($flight_awb) {
                    $data_awb->data_flightawb = $flight_awb;
                }
            }
            
          
        }

        return $job;
    }


    
}
