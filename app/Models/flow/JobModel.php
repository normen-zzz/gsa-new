<?php

namespace App\Models\flow;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class JobModel extends Model
{
    public function getJob($search = '', $limit = 10)

    {
        $job =  DB::table('job')
            ->join('awb', 'job.id_awb', '=', 'awb.id_awb')
            ->join('customers as agent', 'job.agent', '=', 'agent.id_customer')
            ->join('customers as consignee', 'job.consignee', '=', 'consignee.id_customer')
            ->join('airports as pol', 'awb.pol', '=', 'pol.id_airport')
            ->join('airports as pod', 'awb.pod', '=', 'pod.id_airport')
            ->where(function ($query) use ($search) {
                $query->where('awb.awb', 'like', '%' . $search . '%')
                    ->orWhere('agent.name_customer', 'like', '%' . $search . '%')
                    ->orWhere('consignee.name_customer', 'like', '%' . $search . '%')
                    ->orWhere('pol.name_airport', 'like', '%' . $search . '%')
                    ->orWhere('pod.name_airport', 'like', '%' . $search . '%');
            })
            ->orderBy('job.id_job', 'desc')
            ->select(
                'job.*',
                'awb.*',
                'agent.name_customer as agent_name',
                'consignee.name_customer as consignee_name',
                'pol.name_airport as pol_name',
                'pod.name_airport as pod_name'
            );
            return $limit > 0 ? $job->paginate($limit) : $job->get();
    }
}
