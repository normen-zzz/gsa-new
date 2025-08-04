<?php

namespace App\Http\Controllers\flow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\flow\JobModel;
use Illuminate\Validation\ValidationException;
use App\Helpers\ResponseHelper;

date_default_timezone_set('Asia/Jakarta');

class JobController extends Controller
{
    public function updateJob(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'id_job' => 'required|integer|exists:job,id_job',
                'awb' => 'required|string|max:50',
                'agent' => 'required|integer|exists:customers,id_customer',
                'data_agent' => 'required|integer|exists:data_customer,id_datacustomer',
                'etd' => 'required|date',
                'eta' => 'required|date',
                'pol' => 'required|integer|exists:airports,id_airport',
                'pod' => 'required|integer|exists:airports,id_airport',
                'commodity' => 'required|string|max:255',
                'gross_weight' => 'required|numeric|min:0',
                'chargeable_weight' => 'required|numeric|min:0',
                'pieces' => 'required|integer|min:1',
                'special_instructions' => 'nullable|string|max:500',
                'dimensions_job' => 'nullable|array',
                'flight_job' => 'nullable|array',
            ]);

            // Get job and related data
            $job = DB::table('job')->where('id_job', $request->id_job)->first();
            $flight_job = DB::table('flight_job')->where('id_job', $request->id_job)->get();
            $dimensions_job = DB::table('dimension_job')->where('id_job', $request->id_job)->get();

            // Check if job already has AWB
            $awb = DB::table('awb')->where('id_job', $job->id_job)->first();
            if ($awb) {
                throw new Exception('Job already has an associated AWB. Please update the AWB instead.');
            }

            // Prepare job data for update
            $dataJob = [
                'id_shippinginstruction' => $job->id_shippinginstruction,
                'awb' => $request->awb,
                'agent' => $request->agent,
                'data_agent' => $request->data_agent,
                'consignee' => $job->consignee, // Assuming this is not being updated
                'etd' => $request->etd,
                'eta' => $request->eta,
                'pol' => $request->pol,
                'pod' => $request->pod,
                'commodity' => $request->commodity,
                'gross_weight' => $request->gross_weight,
                'chargeable_weight' => $request->chargeable_weight,
                'pieces' => $request->pieces,
                'special_instructions' => $request->special_instructions,
                'updated_by' => $request->user()->id_user,
                'updated_at' => now(),
            ];

            // Update job
            $insertUpdateJob = DB::table('job')->where('id_job', $job->id_job)->update($dataJob);

            if ($insertUpdateJob) {
                // Update or insert dimensions
                if (isset($request->dimensions_job) && is_array($request->dimensions_job)) {
                    foreach ($request->dimensions_job as $dimension) {
                        if (isset($dimension['id_dimensionjob']) && $dimension['id_dimensionjob']) {
                            // Update existing dimension
                            DB::table('dimension_job')
                                ->where('id_dimensionjob', $dimension['id_dimensionjob'])
                                ->update([
                                    'pieces' => $dimension['pieces'],
                                    'length' => $dimension['length'],
                                    'width' => $dimension['width'],
                                    'height' => $dimension['height'],
                                    'weight' => $dimension['weight'],
                                    'remarks' => $dimension['remarks'],
                                    'updated_at' => now(),
                                    'updated_by' => $request->user()->id_user,
                                ]);
                        } else {
                            // Insert new dimension
                            DB::table('dimension_job')->insert([
                                'id_job' => $job->id_job,
                                'pieces' => $dimension['pieces'],
                                'length' => $dimension['length'],
                                'width' => $dimension['width'],
                                'height' => $dimension['height'],
                                'weight' => $dimension['weight'],
                                'remarks' => $dimension['remarks'],
                                'created_at' => now(),
                                'updated_at' => now(),
                                'created_by' => $request->user()->id_user,
                            ]);
                        }
                    }
                }

                // Update or insert flight information
                if (isset($request->flight_job) && is_array($request->flight_job)) {
                    foreach ($request->flight_job as $flight) {
                        if (isset($flight['id_flightjob']) && $flight['id_flightjob']) {
                            // Update existing flight
                            DB::table('flight_job')
                                ->where('id_flightjob', $flight['id_flightjob'])
                                ->update([
                                    'flight_number' => $flight['flight_number'],
                                    'departure' => $flight['departure'],
                                    'departure_timezone' => $flight['departure_timezone'],
                                    'arrival' => $flight['arrival'],
                                    'arrival_timezone' => $flight['arrival_timezone'],
                                    'updated_at' => now(),
                                    'updated_by' => $request->user()->id_user,
                                ]);
                        } else {
                            // Insert new flight
                            DB::table('flight_job')->insert([
                                'id_job' => $job->id_job,
                                'flight_number' => $flight['flight_number'],
                                'departure' => $flight['departure'],
                                'departure_timezone' => $flight['departure_timezone'],
                                'arrival' => $flight['arrival'],
                                'arrival_timezone' => $flight['arrival_timezone'],
                                'created_at' => now(),
                                'updated_at' => now(),
                                'created_by' => $request->user()->id_user,
                            ]);
                        }
                    }
                }

                // Log job changes
                $log_job = [
                    'id_job' => $job->id_job,
                    'action' => json_encode([
                        'before' => [
                            'awb' => $job->awb,
                            'agent' => $job->agent,
                            'data_agent' => $job->data_agent,
                            'etd' => $job->etd,
                            'eta' => $job->eta,
                            'pol' => $job->pol,
                            'pod' => $job->pod,
                            'commodity' => $job->commodity,
                            'gross_weight' => $job->gross_weight,
                            'chargeable_weight' => $job->chargeable_weight,
                            'pieces' => $job->pieces,
                            'special_instructions' => $job->special_instructions,
                            'flight_job' => $flight_job,
                            'dimensions_job' => $dimensions_job
                        ],
                        'after' => [
                            'awb' => $dataJob['awb'],
                            'agent' => $dataJob['agent'],
                            'data_agent' => $dataJob['data_agent'],
                            'etd' => $dataJob['etd'],
                            'eta' => $dataJob['eta'],
                            'pol' => $dataJob['pol'],
                            'pod' => $dataJob['pod'],
                            'commodity' => $dataJob['commodity'],
                            'gross_weight' => $dataJob['gross_weight'],
                            'chargeable_weight' => $dataJob['chargeable_weight'],
                            'pieces' => $dataJob['pieces'],
                            'special_instructions' => $dataJob['special_instructions'],
                            'flight_job' => $request->flight_job,
                            'dimensions_job' => $request->dimensions_job
                        ]
                    ]),
                    'id_user' => $request->user()->id_user,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                DB::table('log_job')->insert($log_job);
                DB::commit();

                return ResponseHelper::success('Job updated successfully.', NULL, 200);
            } else {
                throw new Exception('Failed to update job.');
            }
        } catch (Exception $th) {
            DB::rollBack();
            return ResponseHelper::error($th);
        }
    }

    public function getJob(Request $request)
    {
        try {
            $jobModel = new JobModel();
            $limit = $request->input('limit', 10);
            $search = $request->input('searchKey', '');
            $jobs = $jobModel->getJob($search, $limit);
            return ResponseHelper::success('Jobs retrieved successfully.', $jobs, 200);
        } catch (Exception $th) {
            return ResponseHelper::error($th);
        }
    }

    public function getJobById(Request $request)
    {
        try {
            $jobModel = new JobModel();
            $id = $request->input('id');
            $job = $jobModel->getJobById($id);
            if (!$job) {
                return ResponseHelper::success('Job not found.', NULL, 404);
            }
            return ResponseHelper::success('Job retrieved successfully.', $job, 200);
        } catch (Exception $th) {
            return ResponseHelper::error($th);
        }
    }

    public function executeJob(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_job' => 'required|integer|exists:job,id_job|unique:awb,id_job',
                'agent' => 'required|integer|exists:customers,id_customer',
                'data_agent' => 'required|integer|exists:data_customer,id_datacustomer',
                'consignee' => 'required|string|max:255',
                'awb' => 'required|string|max:50',
                'etd' => 'required|date',
                'eta' => 'required|date',
                'pol' => 'required|integer|exists:airports,id_airport',
                'pod' => 'required|integer|exists:airports,id_airport',
                'gross_weight' => 'required|numeric|min:0',
                'chargeable_weight' => 'required|numeric|min:0',
                'pieces' => 'required|integer|min:1',
                'commodity' => 'required|string|max:255',
                'special_instructions' => 'nullable|string|max:500',
                'dimensions' => 'nullable|array',
                'flight' => 'nullable|array',
            ], [
                'id_job.unique' => 'This job has already been executed and an AWB has been created.',

            ]);

            $dataAwb = [
                'id_job' => $request->id_job,
                'agent' => $request->agent,
                'data_agent' => $request->data_agent,
                'consignee' => $request->consignee,
                'awb' => $request->awb,
                'etd' => $request->etd,
                'eta' => $request->eta,
                'pol' => $request->pol,
                'pod' => $request->pod,
                'commodity' => $request->commodity,
                'gross_weight' => $request->gross_weight,
                'chargeable_weight' => $request->chargeable_weight,
                'pieces' => $request->pieces,
                'special_instructions' => $request->special_instructions,
                'created_by' => $request->user()->id_user,
                'created_at' => now(),
                'updated_at' => now(),
                'status' => 'awb_received_by_ops',
            ];
            $insertDataAwb = DB::table('awb')->insert($dataAwb);
            $id_awb = DB::getPdo()->lastInsertId();

            if ($insertDataAwb) {
                if (isset($request->dimensions) && is_array($request->dimensions)) {
                    foreach ($request->dimensions as $dimension) {
                        DB::table('dimension_awb')->insert([
                            'id_awb' => $id_awb,
                            'pieces' => $dimension['pieces'],
                            'length' => $dimension['length'],
                            'width' => $dimension['width'],
                            'height' => $dimension['height'],
                            'weight' => $dimension['weight'],
                            'remarks' => $dimension['remarks'],
                            'created_at' => now(),
                            'updated_at' => now(),
                            'created_by' => $request->user()->id_user,
                        ]);
                    }
                }
                if (isset($request->flight) && is_array($request->flight)) {
                    foreach ($request->flight as $flight) {
                        DB::table('flight_awb')->insert([
                            'id_awb' => $id_awb,
                            'flight_number' => $flight['flight_number'],
                            'departure' => $flight['departure'],
                            'departure_timezone' => $flight['departure_timezone'],
                            'arrival' => $flight['arrival'],
                            'arrival_timezone' => $flight['arrival_timezone'],
                            'created_at' => now(),
                            'updated_at' => now(),
                            'created_by' => $request->user()->id_user,
                        ]);
                    }
                }
                DB::table('log_awb')->insert([
                    'id_awb' => $id_awb,
                    'action' => json_encode(['status' => 'awb_received_by_ops']),
                    'id_user' => $request->user()->id_user,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                throw new Exception('Failed to insert AWB data.');
            }
            DB::commit();
            return ResponseHelper::success('Job executed successfully.', NULL, 201);
        } catch (Exception $th) {
            DB::rollBack();
            return ResponseHelper::error($th);
        }
    }

    public function getAwb(Request $request)
    {
        $limit = $request->input('limit', 10);
        $searchKey = $request->input('searchKey', '');

        $awb = DB::table('awb')
            ->leftJoin('job', 'awb.id_job', '=', 'job.id_job')
            ->leftJoin('customers as agent', 'awb.agent', '=', 'agent.id_customer')
            ->leftJoin('airports as pol', 'awb.pol', '=', 'pol.id_airport')
            ->leftJoin('airports as pod', 'awb.pod', '=', 'pod.id_airport')
            ->select(
                'awb.id_awb',
                'awb.id_job',
                'awb.agent',
                'customers.name_customer as agent_name',
                'awb.data_agent as id_data_agent',
                'data_customer.data as data_agent',
                'awb.awb',
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
            )
            ->when(!empty($searchKey), function ($query) use ($searchKey) {
                return $query->where(function ($q) use ($searchKey) {
                    $q->where('awb.awb', 'like', '%' . $searchKey . '%')
                        ->orWhere('customers.name_customer', 'like', '%' . $searchKey . '%')
                        ->orWhere('pol.name_airport', 'like', '%' . $searchKey . '%')
                        ->orWhere('pod.name_airport', 'like', '%' . $searchKey . '%');
                });
            })
            ->orderBy('awb.id_awb', 'desc')
            ->paginate($limit);
        $awb->getCollection()->transform(function ($item) {
            $dimensions = DB::table('dimension_awb')
                ->where('id_awb', $item->id_awb)
                ->get();
            if ($dimensions) {
                $item->dimension_awb = $dimensions;
            } else {
                $item->dimension_awb = [];
            }
        });
        return $awb;
    }

    public function finishExecuteJob(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_awb' => 'required|integer|exists:awb,id_awb',
            ]);

            $updateAwb = DB::table('awb')->where('id_awb', $request->id_awb)->first();
            if (!$updateAwb) {
                throw new Exception('AWB not found.');
            }

            DB::table('awb')->where('id_awb', $request->id_awb)->update([
                'status' => 'awb_handled_by_ops',
                'updated_at' => now(),
                'updated_by' => $request->user()->id_user,
            ]);
            DB::table('log_awb')->insert([
                'id_awb' => $request->id_awb,
                'action' => json_encode(['status' => 'job_handled_by_ops']),
                'id_user' => $request->user()->id_user,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
            return ResponseHelper::success('AWB status updated successfully.', NULL, 200);
        } catch (Exception $th) {
            DB::rollBack();
            return ResponseHelper::error($th);
        }
    }

    public function updateExecuteJob(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_awb' => 'required|integer|exists:awb,id_awb',
                'agent' => 'required|integer|exists:customers,id_customer',
                'data_agent' => 'required|integer|exists:data_customer,id_datacustomer',
                'awb' => 'required|string|max:50',
                'etd' => 'required|date',
                'eta' => 'required|date',
                'pol' => 'required|integer|exists:airports,id_airport',
                'pod' => 'required|integer|exists:airports,id_airport',
                'gross_weight' => 'required|numeric|min:0',
                'chargeable_weight' => 'required|numeric|min:0',
                'pieces' => 'required|integer|min:1',
                'commodity' => 'required|string|max:255',
                'special_instructions' => 'nullable|string|max:500',
                'dimensions' => 'nullable|array',
                'flight' => 'nullable|array',
            ]);

            $awb = DB::table('awb')->where('id_awb', $request->id_awb)->first();
            $dimension_awb = DB::table('dimension_awb')->where('id_awb', $request->id_awb)->get();
            $flight_awb = DB::table('flight_awb')->where('id_awb', $request->id_awb)->get();

            $dataAwb = [
                'agent' => $request->agent,
                'data_agent' => $request->data_agent,
                'awb' => $request->awb,
                'etd' => $request->etd,
                'eta' => $request->eta,
                'pol' => $request->pol,
                'pod' => $request->pod,
                'commodity' => $request->commodity,
                'gross_weight' => $request->gross_weight,
                'chargeable_weight' => $request->chargeable_weight,
                'pieces' => $request->pieces,
                'special_instructions' => $request->special_instructions,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_user,
            ];

            DB::table('awb')->where('id_awb', $request->id_awb)->update($dataAwb);

            if (isset($request->dimensions) && is_array($request->dimensions)) {
                foreach ($request->dimensions as $dimension) {
                    if (isset($dimension['id_dimensionawb']) && $dimension['id_dimensionawb']) {
                        DB::table('dimension_awb')
                            ->where('id_dimensionawb', $dimension['id_dimensionawb'])
                            ->update([
                                'pieces' => $dimension['pieces'],
                                'length' => $dimension['length'],
                                'width' => $dimension['width'],
                                'height' => $dimension['height'],
                                'weight' => $dimension['weight'],
                                'remarks' => $dimension['remarks'],
                                'updated_at' => now(),
                                'updated_by' => $request->user()->id_user,
                            ]);
                    } else {
                        DB::table('dimension_awb')->insert([
                            'id_awb' => $request->id_awb,
                            'pieces' => $dimension['pieces'],
                            'length' => $dimension['length'],
                            'width' => $dimension['width'],
                            'height' => $dimension['height'],
                            'weight' => $dimension['weight'],
                            'remarks' => $dimension['remarks'],
                            'created_at' => now(),
                            'updated_at' => now(),
                            'created_by' => $request->user()->id_user,
                        ]);
                    }
                }
            }
            if (isset($request->flight) && is_array($request->flight)) {
                foreach ($request->flight as $flight) {
                    if (isset($flight['id_flightawb']) && $flight['id_flightawb']) {
                        DB::table('flight_awb')
                            ->where('id_flightawb', $flight['id_flightawb'])
                            ->update([
                                'flight_number' => $flight['flight_number'],
                                'departure' => $flight['departure'],
                                'departure_timezone' => $flight['departure_timezone'],
                                'arrival' => $flight['arrival'],
                                'arrival_timezone' => $flight['arrival_timezone'],
                                'updated_at' => now(),
                                'updated_by' => $request->user()->id_user,
                            ]);
                    } else {
                        DB::table('flight_awb')->insert([
                            'id_awb' => $request->id_awb,
                            'flight_number' => $flight['flight_number'],
                            'departure' => $flight['departure'],
                            'departure_timezone' => $flight['departure_timezone'],
                            'arrival' => $flight['arrival'],
                            'arrival_timezone' => $flight['arrival_timezone'],
                            'created_at' => now(),
                            'updated_at' => now(),
                            'created_by' => $request->user()->id_user,
                        ]);
                    }
                }
            }




            DB::table('log_awb')->insert([
                'id_awb' => $request->id_awb,
                'action' => json_encode([
                    'before' => [
                        'awb' => $awb->awb,
                        'agent' => $awb->agent,
                        'data_agent' => $awb->data_agent,
                        'etd' => $awb->etd,
                        'eta' => $awb->eta,
                        'pol' => $awb->pol,
                        'pod' => $awb->pod,
                        'commodity' => $awb->commodity,
                        'gross_weight' => $awb->gross_weight,
                        'chargeable_weight' => $awb->chargeable_weight,
                        'pieces' => $awb->pieces,
                        'special_instructions' => $awb->special_instructions,
                        'dimension_awb' => $dimension_awb,
                        'flight_awb' => $flight_awb
                    ],
                    'after' => [
                        'awb' => $dataAwb['awb'],
                        'agent' => $dataAwb['agent'],
                        'data_agent' => $dataAwb['data_agent'],
                        'etd' => $dataAwb['etd'],
                        'eta' => $dataAwb['eta'],
                        'pol' => $dataAwb['pol'],
                        'pod' => $dataAwb['pod'],
                        'commodity' => $dataAwb['commodity'],
                        'gross_weight' => $dataAwb['gross_weight'],
                        'chargeable_weight' => $dataAwb['chargeable_weight'],
                        'pieces' => $dataAwb['pieces'],
                        'special_instructions' => $dataAwb['special_instructions'],
                        'dimension_awb' => $request->dimensions,
                        'flight_awb' => $request->flight
                    ]
                ]),
                'id_user' => $request->user()->id_user,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
            return ResponseHelper::success('Job status updated successfully.', NULL, 200);
        } catch (Exception $th) {
            DB::rollBack();
            return ResponseHelper::error($th);
        }
    }

    public function addHawb(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_awb' => 'required|integer|exists:awb,id_awb',
                'hawb_number' => 'required|string|max:50|unique:hawb,hawb_number',
                'dimensions' => 'nullable|array',
                'dimensions.*.id_dimensionawb' => 'nullable|integer|exists:dimension_awb,id_dimensionawb|unique:dimension_hawb,id_dimensionawb',
            ], [
                'hawb_number.unique' => 'This HAWB number already exists.',
                'id_awb.exists' => 'The specified AWB does not exist.',
                'dimensions.*.id_dimensionawb.exists' => 'One or more dimensions do not exist.',
            ]);

            $dataHawb = [
                'id_awb' => $request->id_awb,
                'hawb_number' => $request->hawb_number,
                'created_by' => $request->user()->id_user,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $insertHawb = DB::table('hawb')->insert($dataHawb);
            $id_hawb = DB::getPdo()->lastInsertId();
            if ($insertHawb) {
                if (isset($request->dimensions) && is_array($request->dimensions)) {
                    foreach ($request->dimensions as $dimension) {
                        $dimensionHawb = [
                            'id_hawb' => $id_hawb,
                            'id_dimensionawb' => $dimension['id_dimensionawb'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'created_by' => $request->user()->id_user,
                        ];
                        DB::table('dimension_hawb')->insert($dimensionHawb);
                    }
                }

                DB::table('log_hawb')->insert([
                    'id_hawb' => $id_hawb,
                    'action' => json_encode([
                        'hawb_number' => $dataHawb['hawb_number'],
                        'id_awb' => $dataHawb['id_awb'],
                        'dimensions' => $request->dimensions ?? []
                    ]),
                    'id_user' => $request->user()->id_user,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();
                return ResponseHelper::success('HAWB created successfully.', NULL, 201);
            } else {
                throw new Exception('Failed to create HAWB.');
            }
        } catch (Exception $e) {
            //throw $th;
        }
    }

    public function updateHawb(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_hawb' => 'required|integer|exists:hawb,id_hawb',
                'hawb_number' => 'required|string|max:50|unique:hawb,hawb_number,' . $request->id_hawb . ',id_hawb',
                'dimensions' => 'nullable|array',
                'dimensions.*.id_dimensionhawb' => 'nullable|integer|exists:dimension_hawb,id_dimensionhawb',
            ], [
                'hawb_number.unique' => 'This HAWB number already exists.',
            ]);

            $hawb = DB::table('hawb')->where('id_hawb', $request->id_hawb)->first();
            if (!$hawb) {
                throw new Exception('HAWB not found.');
            }

            $dataHawb = [
                'hawb_number' => $request->hawb_number,
                'updated_at' => now(),
                'updated_by' => $request->user()->id_user,
            ];

            DB::table('hawb')->where('id_hawb', $request->id_hawb)->update($dataHawb);

            if (isset($request->dimensions) && is_array($request->dimensions)) {
                foreach ($request->dimensions as $dimension) {
                    if (isset($dimension['id_dimensionhawb']) && $dimension['id_dimensionhawb']) {
                        DB::table('dimension_hawb')
                            ->where('id_dimensionhawb', $dimension['id_dimensionhawb'])
                            ->update([
                                'updated_at' => now(),
                                'updated_by' => $request->user()->id_user,
                            ]);
                    } else {
                        DB::table('dimension_hawb')->insert([
                            'id_hawb' => $request->id_hawb,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'created_by' => $request->user()->id_user,
                        ]);
                    }
                }
            }

            DB::table('log_hawb')->insert([
                'id_hawb' => $request->id_hawb,
                'action' => json_encode([
                    'before' => [
                        'hawb_number' => $hawb->hawb_number,
                    ],
                    'after' => [
                        'hawb_number' => $dataHawb['hawb_number'],
                        'dimensions' => $request->dimensions ?? []
                    ]
                ]),
                'id_user' => $request->user()->id_user,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
            return ResponseHelper::success('HAWB updated successfully.', NULL, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function getExecuteJob(Request $request)
    {

        $jobModel = new JobModel();
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $jobs = $jobModel->getExecuteJob($search, $limit);
        return ResponseHelper::success('Jobs retrieved successfully.', $jobs, 200);
    }

    public function getExecuteJobById(Request $request)
    {

        $jobModel = new JobModel();
        $jobId = $request->input('id');
        $job = $jobModel->getExecuteJobById($jobId);
        return ResponseHelper::success('Job retrieved successfully.', $job, 200);
    }
}
