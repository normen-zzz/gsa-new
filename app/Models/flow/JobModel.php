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
        'airline',
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
            'data_agent.data as agent_data',
            'job.consignee',
            'job.airline',
            'airlines.name as airline_name',
            'job.awb',
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
            ->leftJoin('data_customer as data_agent', 'job.agent_data', '=', 'data_agent.id_datacustomer')
            ->leftJoin('airports as pol', 'job.pol', '=', 'pol.id_airport')
            ->leftJoin('airports as pod', 'job.pod', '=', 'pod.id_airport')
            ->leftJoin('users as created_by', 'job.created_by', '=', 'created_by.id_user')
            ->leftJoin('users as updated_by', 'job.updated_by', '=', 'updated_by.id_user')
            ->leftJoin('airlines', 'job.airline', '=', 'airlines.id_airline')
            ->select($select)

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
            $item->agent_data = json_decode($item->agent_data, true);
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

            $selectShippinginstruction = [
                'shippinginstruction.id_shippinginstruction',
                'shippinginstruction.agent',
                'agent.name_customer as agent_name',
                'shippinginstruction.data_agent as id_data_agent',
                'data_agent.data as agent_data',
                'shippinginstruction.consignee',
                'shippinginstruction.airline',
                'airlines.name as airline_name',
                'shippinginstruction.type',
                'shippinginstruction.etd',
                'shippinginstruction.eta',
                'shippinginstruction.pol',
                'pol.name_airport as pol_name',
                'shippinginstruction.pod',
                'pod.name_airport as pod_name',
                'shippinginstruction.commodity',
                'shippinginstruction.gross_weight',
                'shippinginstruction.chargeable_weight',
                'shippinginstruction.pieces',
                'shippinginstruction.dimensions',
                'shippinginstruction.special_instructions',
                'shippinginstruction.created_by',
                'created_by.name as created_by_name',
                'shippinginstruction.updated_by',
                'updated_by.name as updated_by_name',
                'shippinginstruction.received_by',
                'received_by.name as received_by_name',
                'shippinginstruction.deleted_by',
                'deleted_by.name as deleted_by_name',
                'shippinginstruction.created_at',
                'shippinginstruction.updated_at',
                'shippinginstruction.received_at',
                'shippinginstruction.deleted_at'
            ];

            $shippingInstruction = DB::table('shippinginstruction')
                ->select(
                    $selectShippinginstruction
                )
                ->leftJoin('customers as agent', 'shippinginstruction.agent', '=', 'agent.id_customer')
                ->leftJoin('data_customer as data_agent', 'shippinginstruction.data_agent', '=', 'data_agent.id_datacustomer')
                ->leftJoin('airports as pol', 'shippinginstruction.pol', '=', 'pol.id_airport')
                ->leftJoin('airports as pod', 'shippinginstruction.pod', '=', 'pod.id_airport')
                ->leftJoin('users as created_by', 'shippinginstruction.created_by', '=', 'created_by.id_user')
                ->leftJoin('users as updated_by', 'shippinginstruction.updated_by', '=', 'updated_by.id_user')
                ->leftJoin('users as received_by', 'shippinginstruction.received_by', '=', 'received_by.id_user')
                ->leftJoin('users as deleted_by', 'shippinginstruction.deleted_by', '=', 'deleted_by.id_user')
                ->leftJoin('airlines', 'shippinginstruction.airline', '=', 'airlines.id_airline')
                ->where('id_shippinginstruction', $item->id_shippinginstruction)
                ->first();
            if ($shippingInstruction) {
                $shippingInstruction->agent_data = json_decode($shippingInstruction->agent_data, true);
                $shippingInstruction->dimensions = json_decode($shippingInstruction->dimensions, true);
                $item->data_shippinginstruction = $shippingInstruction;
            } else {
                $item->data_shippinginstruction = [];
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
            } else {
                $item->data_awb = [];
            }
            return $item;
        });

        return $job;
    }

    public function getJobById($id)
    {
        $job = DB::table('job')
            ->leftJoin('customers as agent', 'job.agent', '=', 'agent.id_customer')
            ->leftJoin('data_customer as data_agent', 'job.data_agent', '=', 'data_agent.id_datacustomer')
            ->leftJoin('airports as pol', 'job.pol', '=', 'pol.id_airport')
            ->leftJoin('airports as pod', 'job.pod', '=', 'pod.id_airport')
            ->leftJoin('users as created_by', 'job.created_by', '=', 'created_by.id_user')
            ->leftJoin('users as updated_by', 'job.updated_by', '=', 'updated_by.id_user')
            ->leftJoin('airlines', 'job.airline', '=', 'airlines.id_airline')

            ->where('job.id_job', $id)
            ->select(
                'job.id_job',
                'job.id_shippinginstruction',
                'job.agent',
                'agent.name_customer as agent_name',
                'job.data_agent as id_data_agent',
                'data_agent.data as agent_data',
                'job.consignee',
                'job.airline',
                'airlines.name as airline_name',
                'job.awb',
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
                'created_by.name as created_by_name',
                'job.updated_by',
                'updated_by.name as updated_by_name'
            )
            ->first();
        if (!$job) {
            throw new Exception('Job not found', 404);
        } else {
            $job->agent_data = json_decode($job->agent_data, true);
            $job->dimensions_job = DB::table('dimension_job')
                ->where('id_job', $job->id_job)
                ->get();

            $job->data_flightjob = DB::table('flight_job')
                ->where('id_job', $job->id_job)
                ->get();

            $job->data_agent = DB::table('data_customer')
                ->where('id_datacustomer', $job->data_agent)
                ->first();
                $job->data_agent->data = json_decode($job->data_agent->data, true);


            $select = [
                'shippinginstruction.id_shippinginstruction',
                'shippinginstruction.agent',
                'agent.name_customer as agent_name',
                'shippinginstruction.data_agent as id_data_agent',
                'data_agent.data as agent_data',
                'shippinginstruction.consignee',
                'shippinginstruction.airline',
                'airlines.name as airline_name',
                'shippinginstruction.type',
                'shippinginstruction.etd',
                'shippinginstruction.eta',
                'shippinginstruction.pol',
                'pol.name_airport as pol_name',
                'shippinginstruction.pod',
                'pod.name_airport as pod_name',
                'shippinginstruction.commodity',
                'shippinginstruction.gross_weight',
                'shippinginstruction.chargeable_weight',
                'shippinginstruction.pieces',
                'shippinginstruction.dimensions',
                'shippinginstruction.special_instructions',
                'shippinginstruction.created_by',
                'created_by.name as created_by_name',
                'shippinginstruction.updated_by',
                'updated_by.name as updated_by_name',
                'shippinginstruction.received_by',
                'received_by.name as received_by_name',
                'shippinginstruction.deleted_by',
                'deleted_by.name as deleted_by_name',
                'shippinginstruction.created_at',
                'shippinginstruction.updated_at',
                'shippinginstruction.received_at',
                'shippinginstruction.deleted_at'
            ];

            $shippingInstruction = DB::table('shippinginstruction')
                ->select(
                    $select
                )
                ->leftJoin('customers as agent', 'shippinginstruction.agent', '=', 'agent.id_customer')
                ->leftJoin('data_customer as data_agent', 'shippinginstruction.data_agent', '=', 'data_agent.id_datacustomer')
                ->leftJoin('airports as pol', 'shippinginstruction.pol', '=', 'pol.id_airport')
                ->leftJoin('airports as pod', 'shippinginstruction.pod', '=', 'pod.id_airport')
                ->leftJoin('users as created_by', 'shippinginstruction.created_by', '=', 'created_by.id_user')
                ->leftJoin('users as updated_by', 'shippinginstruction.updated_by', '=', 'updated_by.id_user')
                ->leftJoin('users as received_by', 'shippinginstruction.received_by', '=', 'received_by.id_user')
                ->leftJoin('users as deleted_by', 'shippinginstruction.deleted_by', '=', 'deleted_by.id_user')
                ->leftJoin('airlines', 'shippinginstruction.airline', '=', 'airlines.id_airline')
                ->where('id_shippinginstruction', $job->id_shippinginstruction)
                ->first();
            if ($shippingInstruction) {
                $shippingInstruction->agent_data = json_decode($shippingInstruction->agent_data, true);
                $shippingInstruction->dimensions = json_decode($shippingInstruction->dimensions, true);
                $job->data_shippinginstruction = $shippingInstruction;
            }

            $selectAwb = [
                'awb.id_awb',
                'awb.id_job',
                'awb.awb',
                'awb.agent',
                'agent.name_customer as agent_name',
                'awb.consignee',
                'awb.airline',
                'airlines.name as airline_name',
                'awb.etd',
                'awb.eta',
                'awb.pol',
                'pol.name_airport as pol_name',
                'awb.pod',
                'pod.name_airport as pod_name',
                'awb.commodity',
                'awb.gross_weight',
                'awb.chargeable_weight',
                'awb.pieces',
                'awb.special_instructions',
                'awb.created_by',
                'created_by.name as created_by_name',
                'awb.updated_by',
                'updated_by.name as updated_by_name',
                'awb.created_at',
                'awb.updated_at',
            ];

            $awb = DB::table('awb')
                ->select($selectAwb)
                ->leftJoin('customers as agent', 'awb.agent', '=', 'agent.id_customer')
                ->leftJoin('airports as pol', 'awb.pol', '=', 'pol.id_airport')
                ->leftJoin('airports as pod', 'awb.pod', '=', 'pod.id_airport')
                ->leftJoin('users as created_by', 'awb.created_by', '=', 'created_by.id_user')
                ->leftJoin('users as updated_by', 'awb.updated_by', '=', 'updated_by.id_user')
                ->leftJoin('airlines', 'awb.airline', '=', 'airlines.id_airline')
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
            } else {
                $job->data_awb = [];
            }
        }

        return $job;
    }

    public function getExecuteJob($search, $limit)
    {

        $selectAwb = [
            'awb.id_awb',
            'awb.id_job',
            'job.id_shippinginstruction',
            'awb.awb',
            'awb.agent',
            'agent.name_customer as agent_name',
            'awb.data_agent as id_data_agent',
            'data_agent.data as agent_data',
            'awb.consignee',
            'awb.airline',
            'airlines.name as airline_name',
            'awb.etd',
            'awb.eta',
            'awb.pol',
            'pol.name_airport as pol_name',
            'awb.pod',
            'pod.name_airport as pod_name',
            'awb.commodity',
            'awb.gross_weight',
            'awb.chargeable_weight',
            'awb.pieces',
            'awb.special_instructions',
            'awb.created_by',
            'created_by.name as created_by_name',
            'awb.updated_by',
            'updated_by.name as updated_by_name',
            'awb.created_at',
            'awb.updated_at',
            'awb.status'
        ];
        $awb = DB::table('awb')
            ->select($selectAwb)
            ->leftJoin('customers as agent', 'awb.agent', '=', 'agent.id_customer')
            ->leftJoin('data_customer as data_agent', 'awb.data_agent', '=', 'data_agent.id_datacustomer')
            ->leftJoin('job', 'awb.id_job', '=', 'job.id_job')
            ->leftJoin('airports as pol', 'awb.pol', '=', 'pol.id_airport')
            ->leftJoin('airports as pod', 'awb.pod', '=', 'pod.id_airport')
            ->leftJoin('users as created_by', 'awb.created_by', '=', 'created_by.id_user')
            ->leftJoin('users as updated_by', 'awb.updated_by', '=', 'updated_by.id_user')
            ->leftJoin('airlines', 'awb.airline', '=', 'airlines.id_airline')
            ->when(!empty($search), function ($query) use ($search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('agent.name_customer', 'like', '%' . $search . '%')
                        ->orWhere('awb.consignee', 'like', '%' . $search . '%')
                        ->orWhere('pol.name_airport', 'like', '%' . $search . '%')
                        ->orWhere('pod.name_airport', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('awb.id_awb', 'asc')
            ->paginate($limit);

        $awb->getCollection()->transform(function ($item) {
            // Add dimension data to each AWB
            $dimension_awb = DB::table('dimension_awb')
                ->where('id_awb', $item->id_awb)
                ->get();
            if ($dimension_awb) {
                $item->dimensions_awb = $dimension_awb;
            }

            // Add flight data to each AWB
            $flight_awb = DB::table('flight_awb')
                ->where('id_awb', $item->id_awb)
                ->get();
            if ($flight_awb) {
                $item->data_flightawb = $flight_awb;
            } else {
                $item->data_flightawb = [];
            }

            // Add shipping instruction data
            $shippingInstruction = DB::table('shippinginstruction')
                ->select(
                    'shippinginstruction.id_shippinginstruction',
                    'shippinginstruction.agent',
                    'agent.name_customer as agent_name',
                    'shippinginstruction.data_agent as id_data_agent',
                    'data_agent.data as agent_data',
                    'shippinginstruction.consignee',
                    'shippinginstruction.airline',
                    'airlines.name as airline_name',
                    'shippinginstruction.type',
                    'shippinginstruction.etd',
                    'shippinginstruction.eta',
                    'shippinginstruction.pol',
                    'pol.name_airport as pol_name',
                    'shippinginstruction.pod',
                    'pod.name_airport as pod_name',
                    'shippinginstruction.commodity',
                    'shippinginstruction.gross_weight',
                    'shippinginstruction.chargeable_weight',
                    'shippinginstruction.pieces',
                    'shippinginstruction.dimensions',
                    'shippinginstruction.special_instructions',
                    'shippinginstruction.created_by',
                    'created_by.name as created_by_name',
                    'shippinginstruction.updated_by',
                    'updated_by.name as updated_by_name'
                )
                ->leftJoin('customers as agent', 'shippinginstruction.agent', '=', 'agent.id_customer')
                ->leftJoin('data_customer as data_agent', 'shippinginstruction.data_agent', '=', 'data_agent.id_datacustomer')
                ->leftJoin('airports as pol', 'shippinginstruction.pol', '=', 'pol.id_airport')
                ->leftJoin('airports as pod', 'shippinginstruction.pod', '=', 'pod.id_airport')
                ->leftJoin('users as created_by', 'shippinginstruction.created_by', '=', 'created_by.id_user')
                ->leftJoin('users as updated_by', 'shippinginstruction.updated_by', '=', 'updated_by.id_user')
                ->leftJoin('airlines', 'shippinginstruction.airline', '=', 'airlines.id_airline')
                ->where('id_shippinginstruction', $item->id_shippinginstruction)
                ->first();
            if ($shippingInstruction) {
                $shippingInstruction->agent_data = json_decode($shippingInstruction->agent_data, true);
                $item->data_shippinginstruction = $shippingInstruction;
                $item->data_shippinginstruction->dimensions = json_decode($item->data_shippinginstruction->dimensions, true);
            } else {
                $item->data_shippinginstruction = [];
            }

            // Add job data
            $job = DB::table('job')
                ->leftJoin('customers as agent', 'job.agent', '=', 'agent.id_customer')
                ->leftJoin('data_customer as data_agent', 'job.data_agent', '=', 'data_agent.id_datacustomer')
                ->leftJoin('airports as pol', 'job.pol', '=', 'pol.id_airport')
                ->leftJoin('airports as pod', 'job.pod', '=', 'pod.id_airport')
                ->leftJoin('users as created_by', 'job.created_by', '=', 'created_by.id_user')
                ->leftJoin('users as updated_by', 'job.updated_by', '=', 'updated_by.id_user')
                ->leftJoin('airlines', 'job.airline', '=', 'airlines.id_airline')
                ->where('job.id_job', $item->id_job)
                ->select(
                    'job.id_job',
                    'job.id_shippinginstruction',
                    'job.agent',
                    'agent.name_customer as agent_name',
                    'job.data_agent as id_data_agent',
                    'data_agent.data as agent_data',
                    'job.consignee',
                    'job.airline',
                    'airlines.name as airline_name',
                    'job.awb',
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
                    'created_by.name as created_by_name',
                    'job.updated_by',
                    'updated_by.name as updated_by_name'
                )
                ->first();
            if ($job) {
                $job->agent_data = json_decode($job->agent_data, true);
                $job->dimensions_job = DB::table('dimension_job')
                    ->where('id_job', $job->id_job)
                    ->get();
                $job->data_flightjob = DB::table('flight_job')
                    ->where('id_job', $job->id_job)
                    ->get();
                $item->data_job = $job;
            } else {
                $item->data_job = [];
            }

            return $item;
        });
        return $awb;
    }

    public function getExecuteJobById($id_awb)
    {
        $selectAwb = [
            'awb.id_awb',
            'awb.id_job',
            'job.id_shippinginstruction',
            'awb.awb',
            'awb.agent',
            'agent.name_customer as agent_name',
            'awb.data_agent as id_data_agent',
            'data_agent.data as agent_data',
            'awb.consignee',
            'awb.airline',
            'airlines.name as airline_name',
            'awb.etd',
            'awb.eta',
            'awb.pol',
            'pol.name_airport as pol_name',
            'awb.pod',
            'pod.name_airport as pod_name',
            'awb.commodity',
            'awb.gross_weight',
            'awb.chargeable_weight',
            'awb.pieces',
            'awb.special_instructions',
            'awb.created_by',
            'created_by.name as created_by_name',
            'awb.updated_by',
            'updated_by.name as updated_by_name',
            'awb.created_at',
            'awb.updated_at',
            'awb.status'
        ];
        $awb = DB::table('awb')
            ->select($selectAwb)
            ->leftJoin('customers as agent', 'awb.agent', '=', 'agent.id_customer')
            ->leftJoin('data_customer as data_agent', 'awb.data_agent', '=', 'data_agent.id_datacustomer')
            ->leftJoin('job', 'awb.id_job', '=', 'job.id_job')
            ->leftJoin('airports as pol', 'awb.pol', '=', 'pol.id_airport')
            ->leftJoin('airports as pod', 'awb.pod', '=', 'pod.id_airport')
            ->leftJoin('users as created_by', 'awb.created_by', '=', 'created_by.id_user')
            ->leftJoin('users as updated_by', 'awb.updated_by', '=', 'updated_by.id_user')
            ->leftJoin('airlines', 'awb.airline', '=', 'airlines.id_airline')
            ->where('awb.id_awb', $id_awb)
            ->first();
        if ($awb) {
            $awb->agent_data = json_decode($awb->agent_data, true);
            $dimension_awb = DB::table('dimension_awb')
                ->where('id_awb', $awb->id_awb)
                ->get();
            if ($dimension_awb) {
                $awb->dimensions_awb = $dimension_awb;
            } else {
                $awb->dimensions_awb = [];
            }
            $flight_awb = DB::table('flight_awb')
                ->where('id_awb', $awb->id_awb)
                ->get();
            if ($flight_awb) {
                $awb->data_flightawb = $flight_awb;
            } else {
                $awb->data_flightawb = [];
            }
            $job = DB::table('job')
                ->leftJoin('customers as agent', 'job.agent', '=', 'agent.id_customer')
                ->leftJoin('data_customer as data_agent', 'job.data_agent', '=', 'data_agent.id_datacustomer')
                ->leftJoin('airports as pol', 'job.pol', '=', 'pol.id_airport')
                ->leftJoin('airports as pod', 'job.pod', '=', 'pod.id_airport')
                ->leftJoin('users as created_by', 'job.created_by', '=', 'created_by.id_user')
                ->leftJoin('users as updated_by', 'job.updated_by', '=', 'updated_by.id_user')
                ->leftJoin('airlines', 'job.airline', '=', 'airlines.id_airline')
                ->where('job.id_job', $awb->id_job)
                ->select(
                    'job.id_job',
                    'job.id_shippinginstruction',
                    'job.agent',
                    'agent.name_customer as agent_name',
                    'job.data_agent as id_data_agent',
                    'data_agent.data as agent_data',
                    'job.data_agent',
                    'job.consignee',
                    'job.airline',
                    'airlines.name as airline_name',
                    'job.awb',
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
                    'created_by.name as created_by_name',
                    'job.updated_by',
                    'updated_by.name as updated_by_name'
                )
                ->first();
            if ($job) {
                $job->agent_data = json_decode($job->agent_data, true);
                $job->dimensions_job = DB::table('dimension_job')
                    ->where('id_job', $job->id_job)
                    ->get();
                $job->data_flightjob = DB::table('flight_job')
                    ->where('id_job', $job->id_job)
                    ->get();
                $awb->data_job = $job;
            } else {
                $awb->data_job = [];
            }
            $shippingInstruction = DB::table('shippinginstruction')
                ->select(
                    'shippinginstruction.id_shippinginstruction',
                    'shippinginstruction.agent',
                    'agent.name_customer as agent_name',
                    'shippinginstruction.data_agent as id_data_agent',
                    'data_agent.data as agent_data',
                    'shippinginstruction.consignee',
                    'shippinginstruction.airline',
                    'airlines.name as airline_name',
                    'shippinginstruction.type',
                    'shippinginstruction.etd',
                    'shippinginstruction.eta',
                    'shippinginstruction.pol',
                    'pol.name_airport as pol_name',
                    'shippinginstruction.pod',
                    'pod.name_airport as pod_name',
                    'shippinginstruction.commodity',
                    'shippinginstruction.gross_weight',
                    'shippinginstruction.chargeable_weight',
                    'shippinginstruction.pieces',
                    'shippinginstruction.dimensions',
                    'shippinginstruction.special_instructions',
                    'shippinginstruction.created_by',
                    'created_by.name as created_by_name',
                    'shippinginstruction.updated_by',
                    'updated_by.name as updated_by_name'
                )
                ->leftJoin('customers as agent', 'shippinginstruction.agent', '=', 'agent.id_customer')
                ->leftJoin('data_customer as data_agent', 'shippinginstruction.data_agent', '=', 'data_agent.id_datacustomer')
                ->leftJoin('airports as pol', 'shippinginstruction.pol', '=', 'pol.id_airport')
                ->leftJoin('airports as pod', 'shippinginstruction.pod', '=', 'pod.id_airport')
                ->leftJoin('users as created_by', 'shippinginstruction.created_by', '=', 'created_by.id_user')
                ->leftJoin('users as updated_by', 'shippinginstruction.updated_by', '=', 'updated_by.id_user')
                ->leftJoin('airlines', 'shippinginstruction.airline', '=', 'airlines.id_airline')
                ->where('id_shippinginstruction', $awb->id_shippinginstruction)
                ->first();
            if ($shippingInstruction) {
                $shippingInstruction->agent_data = json_decode($shippingInstruction->agent_data, true);
                $awb->data_shippinginstruction = $shippingInstruction;
                $awb->data_shippinginstruction->dimensions = json_decode($awb->data_shippinginstruction->dimensions, true);
            } else {
                $awb->data_shippinginstruction = [];
            }
        }
        return $awb;
    }
}
