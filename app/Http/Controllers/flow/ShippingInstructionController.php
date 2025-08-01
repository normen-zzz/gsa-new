<?php

namespace App\Http\Controllers\flow;

use Exception;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Auth;

// date
date_default_timezone_set('Asia/Jakarta');

class ShippingInstructionController extends Controller
{
    public function getShippingInstructions(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'a.id_shippinginstruction',
            'c.name_customer as agent',
            'c.id_customer as id_agent',
            'a.data_agent as id_dataagent',
            'a.consignee as consignee',
            'a.type',
            'a.eta',
            'a.etd',
            'e.name_airport as pol',
            'e.id_airport as id_pol',
            'e.code_airport as code_pol',
            'f.name_airport as pod',
            'f.id_airport as id_pod',
            'f.code_airport as code_pod',
            'a.commodity',
            'a.gross_weight',
            'a.chargeable_weight',
            'a.pieces',
            'a.dimensions',
            'a.special_instructions',
            'b.name as created_by',
            'a.status',
            'a.created_at',


        ];

        $query = DB::table('shippinginstruction AS a')
            ->select($select)
            ->leftJoin('users AS b', 'a.created_by', '=', 'b.id_user')
            ->leftJoin('customers AS c', 'a.agent', '=', 'c.id_customer')
            ->leftJoin('data_customer AS d', 'a.data_agent', '=', 'd.id_datacustomer')
            ->leftJoin('airports AS e', 'a.pol', '=', 'e.id_airport')
            ->leftJoin('airports AS f', 'a.pod', '=', 'f.id_airport')
            ->where('d.is_primary', true)
            ->where('c.name_customer', 'like', '%' . $search . '%')
            ->orWhere('e.name_airport', 'like', '%' . $search . '%')
            ->orWhere('f.name_airport', 'like', '%' . $search . '%')
            ->orderBy('a.created_at', 'desc');



        $instructions = $query->paginate($limit);
        $instructions->getCollection()->transform(function ($instruction) {
            // json decode dimensions
            if ($instruction->dimensions) {
                $instruction->dimensions = json_decode($instruction->dimensions, true);
            } else {
                $instruction->dimensions = [];
            }

            $agentData = DB::table('data_customer')
                ->where('id_customer', $instruction->id_agent)
                ->where('id_datacustomer', $instruction->id_dataagent)
                ->first();

            $job = DB::table('job')
                ->select([
                    'job.id_job',
                    'job.id_shippinginstruction',
                    'job.agent',
                    'agent.name_customer as agent_name',
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
                    'job.created_by',
                    'user.name as created_by_name',
                    'job.status',
                    'job.created_at',
                ])
                ->leftJoin('customers AS agent', 'job.agent', '=', 'agent.id_customer')
                ->leftJoin('airports AS pol', 'job.pol', '=', 'pol.id_airport')
                ->leftJoin('airports AS pod', 'job.pod', '=', 'pod.id_airport')
                ->leftJoin('users AS user', 'job.created_by', '=', 'user.id_user')
                ->where('id_shippinginstruction', $instruction->id_shippinginstruction)
                ->first();

            if ($job) {
                $instruction->job_data = $job;
                $job_data = $instruction->job_data;
                $data_flightjob = DB::table('flight_job')
                    ->select([
                        'flight_job.id_flightjob',
                        'flight_job.flight_number',
                        'flight_job.departure',
                        'flight_job.departure_timezone',
                        'flight_job.arrival',
                        'flight_job.arrival_timezone',
                        'flight_job.created_by',
                        'user.name as created_by_name',
                        'flight_job.created_at',
                        'flight_job.updated_at',
                        'flight_job.deleted_at',
                    ])
                    ->leftJoin('users AS user', 'flight_job.created_by', '=', 'user.id_user')
                    ->where('id_job', $job_data->id_job)
                    ->get();
                $dimension_job = DB::table('dimension_job')
                    ->select([
                        'dimension_job.id_dimensionjob',
                        'dimension_job.pieces',
                        'dimension_job.length',
                        'dimension_job.width',
                        'dimension_job.height',
                        'dimension_job.weight',
                        'dimension_job.remarks',
                        'dimension_job.created_by',
                        'user.name as created_by_name',
                        'dimension_job.created_at',
                        'dimension_job.updated_at',
                    ])
                    ->leftJoin('users AS user', 'dimension_job.created_by', '=', 'user.id_user')
                    ->where('id_job', $job_data->id_job)
                    ->get();
                $job_data->data_flightjob = $data_flightjob;
                $job_data->dimensions_job = $dimension_job;

                $awb = DB::table('awb')
                    ->where('id_job', $job->id_job)
                    ->first();
                if ($awb) {
                    $instruction->awb_data = $awb;
                    $awb_data = $instruction->awb_data;
                    $dimensions_awb = DB::table('dimension_awb')
                        ->where('id_awb', $awb_data->id_awb)
                        ->get();
                    if ($dimensions_awb) {
                        $awb_data->dimensions = $dimensions_awb;
                    }
                    $data_flightawb = DB::table('flight_awb')
                        ->where('id_awb', $awb_data->id_awb)
                        ->get();
                    if ($data_flightawb) {
                        $awb_data->data_flight = $data_flightawb;
                    }
                }
            }



            $agentData->id_datacustomer = $agentData ? $agentData->id_datacustomer : null;
            $instruction->agent_data = $agentData ? json_decode($agentData->data, true) : [];
            return $instruction;
        });



        return ResponseHelper::success('Shipping instructions retrieved successfully.', $instructions, 200);
    }

    public function getShippingInstructionById(Request $request)
    {
        $id = $request->input('id');

        $select = [
            'a.id_shippinginstruction',
            'c.name_customer as agent',
            'c.id_customer as id_agent',
            'a.data_agent as id_dataagent',
            'a.consignee as consignee',
            'a.type',
            'a.eta',
            'a.etd',
            'e.name_airport as pol',
            'e.id_airport as id_pol',
            'e.code_airport as code_pol',
            'f.name_airport as pod',
            'f.id_airport as id_pod',
            'f.code_airport as code_pod',
            'a.commodity',
            'a.gross_weight',
            'a.chargeable_weight',
            'a.pieces',
            'a.dimensions',
            'a.special_instructions',
            'b.name as created_by',
            'a.status'
        ];
        $instruction = DB::table('shippinginstruction AS a')
            ->select($select)
            ->leftJoin('users AS b', 'a.created_by', '=', 'b.id_user')
            ->leftJoin('customers AS c', 'a.agent', '=', 'c.id_customer')
            ->leftJoin('airports AS e', 'a.pol', '=', 'e.id_airport')
            ->leftJoin('airports AS f', 'a.pod', '=', 'f.id_airport')
            ->where('a.id_shippinginstruction', $id)
            ->orderBy('a.id_shippinginstruction', 'desc')->first();

        // json decode dimensions
        if ($instruction && $instruction->dimensions) {
            $instruction->dimensions = json_decode($instruction->dimensions, true);
        } else {
            $instruction->dimensions = [];
        }
        $agentData = DB::table('data_customer')
            ->where('id_customer', $instruction->id_agent)
            ->where('id_datacustomer', $instruction->id_dataagent)
            ->first();

        $instruction->data_agent = $agentData ? json_decode($agentData->data, true) : [];

        $job = DB::table('job')
            ->select([
                'job.id_job',
                'job.id_shippinginstruction',
                'job.agent',
                'agent.name_customer as agent_name',
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
                'job.created_by',
                'user.name as created_by_name',
                'job.status',
                'job.created_at',
            ])
            ->leftJoin('customers AS agent', 'job.agent', '=', 'agent.id_customer')
            ->leftJoin('airports AS pol', 'job.pol', '=', 'pol.id_airport')
            ->leftJoin('airports AS pod', 'job.pod', '=', 'pod.id_airport')
            ->leftJoin('users AS user', 'job.created_by', '=', 'user.id_user')
            ->where('id_shippinginstruction', $instruction->id_shippinginstruction)
            ->first();

        if ($job) {
            $instruction->job_data = $job;
            $job_data = $instruction->job_data;
            $data_flightjob = DB::table('flight_job')
                ->select([
                    'flight_job.id_flightjob',
                    'flight_job.flight_number',
                    'flight_job.departure',
                    'flight_job.departure_timezone',
                    'flight_job.arrival',
                    'flight_job.arrival_timezone',
                    'flight_job.created_by',
                    'user.name as created_by_name',
                    'flight_job.created_at',
                    'flight_job.updated_at',
                    'flight_job.deleted_at',
                ])
                ->leftJoin('users AS user', 'flight_job.created_by', '=', 'user.id_user')
                ->where('id_job', $job_data->id_job)
                ->get();
            $dimension_job = DB::table('dimension_job')
                ->select([
                    'dimension_job.id_dimensionjob',
                    'dimension_job.pieces',
                    'dimension_job.length',
                    'dimension_job.width',
                    'dimension_job.height',
                    'dimension_job.weight',
                    'dimension_job.remarks',
                    'dimension_job.created_by',
                    'user.name as created_by_name',
                    'dimension_job.created_at',
                    'dimension_job.updated_at',
                ])
                ->leftJoin('users AS user', 'dimension_job.created_by', '=', 'user.id_user')
                ->where('id_job', $job_data->id_job)
                ->get();
            if ($dimension_job) {
                $job_data->dimensions_job = $dimension_job;
            }
            if ($data_flightjob) {
                $job_data->flight_job = $data_flightjob;
            }
            $awb = DB::table('awb')
                ->where('id_job', $job->id_job)
                ->first();
            if ($awb) {
                $instruction->awb_data = $awb;
                $awb_data = $instruction->awb_data;
                $dimensions_awb = DB::table('dimension_awb')
                    ->where('id_awb', $awb_data->id_awb)
                    ->get();
                if ($dimensions_awb) {
                    $awb_data->dimensions_awb = $dimensions_awb;
                }
                $data_flightawb = DB::table('flight_awb')
                    ->where('id_awb', $awb_data->id_awb)
                    ->get();
                if ($data_flightawb) {
                    $awb_data->flight_awb = $data_flightawb;
                }
            }
        }


        if (!$instruction) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'meta_data' => [
                    'code' => 404,
                    'message' => 'Shipping instruction not found.',
                ]
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $instruction,
            'meta_data' => [
                'code' => 200,
                'message' => 'Shipping instruction retrieved successfully.',
            ]
        ], 200);
    }

    public function createShippingInstruction(Request $request)
    {
        $data = $request->validate([
            'agent' => 'required|integer',
            'data_agent' => 'required|integer|exists:data_customer,id_datacustomer',
            'consignee' => 'nullable|string',
            'etd' => 'required|date',
            'eta' => 'required|date',
            'pol' => 'required|integer|exists:airports,id_airport',
            'pod' => 'required|integer|exists:airports,id_airport',
            'commodity' => 'nullable|string|max:255',
            'gross_weight' => 'nullable|numeric|min:0',
            'chargeable_weight' => 'nullable|numeric|min:0',
            'pieces' => 'nullable|integer|min:0',
            'special_instructions' => 'nullable|string',
            'dimensions' => 'nullable|array',

        ]);
        $data['created_by'] = $request->user()->id_user;
        // date y-m-d H:i:s
        DB::beginTransaction();
        try {
            $instruction = DB::table('shippinginstruction')
                ->insertGetId([
                    'agent' => $data['agent'],
                    'data_agent' => $data['data_agent'],
                    'consignee' => $data['consignee'],
                    'etd' => date('Y-m-d H:i:s', strtotime($data['etd'])),
                    'eta' => date('Y-m-d H:i:s', strtotime($data['eta'])),
                    'pol' => $data['pol'],
                    'pod' => $data['pod'],
                    'commodity' => $data['commodity'],
                    'dimensions' => json_encode($data['dimensions'] ?? []),
                    'gross_weight' => $data['gross_weight'],
                    'chargeable_weight' => $data['chargeable_weight'],
                    'pieces' => $data['pieces'],
                    'special_instructions' => $data['special_instructions'],
                    'created_by' => $data['created_by'],
                    'status' => 'si_created_by_sales',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            $id_shippinginstruction = DB::getPdo()->lastInsertId();
            if ($instruction) {
                $addDimensions = true;
                if (isset($request->dimensions) && is_array($request->dimensions) && count($request->dimensions) > 0) {
                    foreach ($request->dimensions as $dimension) {
                        $dimensionInsert = DB::table('dimension_shippinginstruction')->insert([
                            'id_shippinginstruction' => $id_shippinginstruction,
                            'pieces' => $dimension['pieces'] ??null, // Default to 1 if not provided
                            'length' => $dimension['length'] ?? null,
                            'width' => $dimension['width'] ?? null,
                            'height' => $dimension['height'] ?? null,
                            'weight' => $dimension['weight'] ?? null,
                            'remarks' => $dimension['remarks'] ?? null,
                            'created_by' => $data['created_by'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        if (!$dimensionInsert) {
                            $addDimensions = false;
                            break;
                        }
                    }
                } else {
                    // If no dimensions provided, consider it successful
                    $addDimensions = false;
                }
                if ($addDimensions) {
                    // add dimensions to data 
                    $data['dimensions'] = $request->dimensions ?? [];
                } else {
                    throw new Exception('Failed to add dimensions to shipping instruction.');
                }

                $log = DB::table('log_shippinginstruction')->insert([
                    'id_shippinginstruction' => $id_shippinginstruction,
                    'created_by' => $data['created_by'],
                    'action' => json_encode(['created' => $data]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                if (!$log) {
                    throw new Exception('Failed to log shipping instruction creation.');
                }
            } else {
                throw new Exception('Failed to create shipping instruction.');
            }
            DB::commit();
            return ResponseHelper::success('Shipping instruction created successfully.', NULL, 201);
        } catch (Exception $e) {
            DB::rollback();
            return ResponseHelper::error($e);
        }
    }

    public function updateShippingInstruction(Request $request)
    {
        $data = $request->validate([
            'id_shippinginstruction' => 'required|integer|exists:shippinginstruction,id_shippinginstruction',
            'agent' => 'required|integer|exists:customers,id_customer',
            'data_agent' => 'required|integer|exists:data_customer,id_datacustomer',
            'consignee' => 'nullable|string',
            'etd' => 'required|date',
            'eta' => 'required|date',
            'pol' => 'required|integer|exists:airports,id_airport',
            'pod' => 'required|integer|exists:airports,id_airport',
            'commodity' => 'nullable|string|max:255',
            'gross_weight' => 'nullable|numeric|min:0',
            'chargeable_weight' => 'nullable|numeric|min:0',
            'pieces' => 'nullable|integer|min:0',
            'special_instructions' => 'nullable|string',
            'dimensions' => 'nullable|array',

        ]);
        $data['updated_by'] = $request->user()->id_user;
        $data['etd'] = date('Y-m-d H:i:s', strtotime($data['etd']));
        $data['eta'] = date('Y-m-d H:i:s', strtotime($data['eta']));
        $id = $request->input('id_shippinginstruction');




        DB::beginTransaction();
        try {
            $shippingInstruction = DB::table('shippinginstruction')
                ->where('id_shippinginstruction', $id)
                ->first();
            $instruction = DB::table('shippinginstruction')
                ->where('id_shippinginstruction', $id)
                ->update($data);
            if ($instruction) {
                DB::table('dimension_shippinginstruction')
                    ->where('id_shippinginstruction', $id)
                    ->delete();

                // add dimensions
                if (isset($request->dimensions) && is_array($request->dimensions) && count($request->dimensions) > 0) {
                    foreach ($request->dimensions as $dimension) {
                        DB::table('dimension_shippinginstruction')->insert([
                            'id_shippinginstruction' => $id,
                            'pieces' => $dimension['pieces'] ?? null, // Default to 1 if not provided
                            'length' => $dimension['length'] ?? null,
                            'width' => $dimension['width'] ?? null,
                            'height' => $dimension['height'] ?? null,
                            'weight' => $dimension['weight'] ?? null,
                            'remarks' => $dimension['remarks'] ?? null,
                            'created_by' => $data['updated_by'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $shippingInstruction->dimensions = $request->dimensions ?? [];
                $log = DB::table('log_shippinginstruction')->insert([
                    'id_shippinginstruction' => $id,
                    'created_by' => $data['updated_by'],
                    'action' => json_encode(['to' => $data, 'from' => $shippingInstruction]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                if (!$log) {
                    throw new Exception('Failed to log shipping instruction update.');
                }
            }
            DB::commit();
            return ResponseHelper::success('Shipping instruction updated successfully.', NULL, 200);
        } catch (Exception $e) {
            DB::rollback();
            return ResponseHelper::error($e);
        }
    }

    public function deleteShippingInstruction(Request $request)
    {
        $id = $request->input('id_shippinginstruction');
        $instruction = DB::table('shippinginstruction')->where('id_shippinginstruction', $id)->first();

        if (!$instruction) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'meta_data' => [
                    'code' => 404,
                    'message' => 'Shipping instruction not found.',
                ]
            ], 404);
        }

        DB::beginTransaction();
        try {
            DB::table('shippinginstruction')
                ->where('id', $id)
                ->update(['status' => 'deleted', 'deleted_at' => now(), 'deleted_by' => Auth::id()]);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'meta_data' => [
                    'code' => 200,
                    'message' => 'Shipping instruction deleted successfully.',
                ]
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'meta_data' => [
                    'code' => 500,
                    'message' => 'Failed to delete shipping instruction: ' . $e->getMessage(),
                ]
            ], 500);
        }
    }

    public function receiveShippingInstruction(Request $request)
    {

        DB::beginTransaction();
        try {
            $request->validate([
                'id_shippinginstruction' => 'required|integer|exists:shippinginstruction,id_shippinginstruction',
                'awb' => 'required|integer|unique:awb,awb',
                'agent' => 'required|integer|exists:customers,id_customer',
                'data_agent' => 'required|integer|exists:data_customer,id_datacustomer',
                'consignee' => 'nullable|string',
                'etd' => 'required|date',
                'eta' => 'required|date',
                'pol' => 'required|integer|exists:airports,id_airport',
                'pod' => 'required|integer|exists:airports,id_airport',
                'commodity' => 'required|string|max:255',
                'gross_weight' => 'required|numeric|min:0',
                'chargeable_weight' => 'required|numeric|min:0',
                'pieces' => 'required|integer|min:0',
                'special_instructions' => 'nullable|string',
                'dimensions' => 'nullable|array',
                'dimensions.*.pieces' => 'required|integer|min:0',
                'dimensions.*.length' => 'required|numeric|min:0',
                'dimensions.*.width' => 'required|numeric|min:0',
                'dimensions.*.height' => 'required|numeric|min:0',
                'dimensions.*.weight' => 'required|numeric|min:0',
                'data_flight' => 'nullable|array',
                'data_flight.*.flight_number' => 'required|string|max:255',
                'data_flight.*.departure' => 'required',
                'data_flight.*.departure_timezone' => 'required|string|max:50',
                'data_flight.*.arrival' => 'required',
                'data_flight.*.arrival_timezone' => 'required|string|max:50',
            ]);

            $shippingInstruction = DB::table('shippinginstruction')
                ->where('id_shippinginstruction', $request->input('id_shippinginstruction'))
                ->first();

            if ($shippingInstruction->status !== 'si_created_by_sales') {
                throw new Exception('Shipping instruction is not in a valid state to be received.');
            }



            $dataJob = [
                'id_shippinginstruction' => $request->input('id_shippinginstruction'),
                'awb' => $request->input('awb'),
                'agent' => $request->input('agent'),
                'data_agent' => $request->input('data_agent'),
                'consignee' => $request->input('consignee'),
                'etd' => date('Y-m-d H:i:s', strtotime($request->input('etd'))),
                'eta' => date('Y-m-d H:i:s', strtotime($request->input('eta'))),
                'pol' => $request->input('pol'),
                'pod' => $request->input('pod'),
                'commodity' => $request->input('commodity'),
                'gross_weight' => $request->input('gross_weight'),
                'chargeable_weight' => $request->input('chargeable_weight'),
                'pieces' => $request->input('pieces'),
                'special_instructions' => $request->input('special_instructions'),
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => Auth::id(),
                'status' => 'job_created_by_cs',
            ];

            $insertJob = DB::table('job')->insertGetId($dataJob);
            if ($insertJob) {
                if ($request->dimensions && is_array($request->dimensions) && count($request->dimensions) > 0) {
                    foreach ($request->dimensions as $key => $value) {
                        $dimension_job = [
                            'id_job' => $insertJob,
                            'pieces' => $value['pieces'], // Default to 1 if not provided
                            'length' => $value['length'],
                            'width' => $value['width'],
                            'height' => $value['height'],
                            'weight' => $value['weight'],
                            'remarks' => $value['remarks'] ?? null,
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        DB::table('dimension_job')->insert($dimension_job);
                    }
                }

                if ($request->data_flight && is_array($request->data_flight) && count($request->data_flight) > 0) {
                    foreach ($request->data_flight as $flight) {
                        $flight_job = [
                            'id_job' => $insertJob,
                            'flight_number' => $flight['flight_number'],
                            'departure' => date('Y-m-d H:i:s', strtotime($flight['departure'])),
                            'departure_timezone' => $flight['departure_timezone'],
                            'arrival' => date('Y-m-d H:i:s', strtotime($flight['arrival'])),
                            'arrival_timezone' => $flight['arrival_timezone'],
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        DB::table('flight_job')->insert($flight_job);
                    }
                }
                $updateStatus = DB::table('shippinginstruction')
                    ->where('id_shippinginstruction', $request->input('id_shippinginstruction'))
                    ->update(['status' => 'si_received_by_cs', 'received_at' => now(), 'received_by' => Auth::id()]);
                if (!$updateStatus) {
                    throw new Exception('Failed to update shipping instruction status.');
                }
            } else {
                throw new Exception('Failed to create job from shipping instruction.');
            }

            DB::commit();
            return ResponseHelper::success('Shipping instruction received successfully.', NULL, 201);
        } catch (Exception $th) {
            DB::rollback();
            return ResponseHelper::error($th);
        }
    }

    public function rejectShippingInstruction(Request $request)
    {


        DB::beginTransaction();
        try {
            $id = $request->input('id');
            $instruction = DB::table('shippinginstruction')->where('id', $id)->first();

            if (!$instruction) {
                throw new Exception('Shipping instruction not found.');
            }
            $update = DB::table('shippinginstruction')
                ->where('id_shippinginstruction', $id)
                ->update(['status' => 'si_rejected_by_cs']);

            if ($update) {
                DB::commit();
                return ResponseHelper::success('Shipping instruction rejected successfully.', NULL, 200);
            } else {
                throw new Exception('Failed to update shipping instruction status.');
            }
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'meta_data' => [
                    'code' => 500,
                    'message' => 'Failed to reject shipping instruction: ' . $e->getMessage(),
                ]
            ], 500);
        }
    }
}
