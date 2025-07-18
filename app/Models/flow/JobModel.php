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
        // Optimasi query untuk kinerja yang lebih baik
        $select = [
            'job.id_job',
            'job.date',
            'agent.name_customer as agent_name',
            'consignee.name_customer as consignee_name',
            'agent.id_customer as agent_id',
            'consignee.id_customer as consignee_id',
            'awb.id_awb',
            'awb.awb',
            'awb.etd',
            'awb.eta',
            'awb.pol',
            'awb.pod',
            'pol.name_airport as pol_name',
            'pod.name_airport as pod_name',
            'awb.commodity',
            'awb.weight',
            'awb.pieces',
            'awb.dimensions',
            'awb.data_flight',
            'awb.special_instructions',
            'job.status'
        ];
        $job = DB::table('job')
            ->join('awb', 'job.id_awb', '=', 'awb.id_awb')
            ->join('customers as agent', 'job.agent', '=', 'agent.id_customer')
            ->join('customers as consignee', 'job.consignee', '=', 'consignee.id_customer')
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
            return $j;
        });

        return $job;
    }
}
