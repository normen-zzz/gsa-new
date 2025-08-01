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
            $job = DB::table('job')->where('id_job', $request->id_job)->first();
            $awb = DB::table('awb')->where('id_awb', $job->id_awb)->first();
            $data = $request->validate([
                'id_job' => 'required|integer|exists:job,id_job',
                'awb' => 'required|string|max:50|unique:awb,awb,' . $awb->awb . ',awb',
                'consignee' => 'nullable|string|max:255',
                'etd' => 'required|date',
                'eta' => 'required|date',
                'commodity' => 'required|string|max:255',
                'gross_weight' => 'required|numeric|min:0',
                'chargeable_weight' => 'required|numeric|min:0',
                'pieces' => 'required|integer|min:1',
                'dimensions' => 'nullable|array',
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
                'updated_at' => now(),
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
                $updateAwb =  DB::table('awb')->where('id_awb', $job->id_awb)->update($dataAwb);
                if ($updateAwb) {
                    $dataAwb['dimensions'] = json_decode($dataAwb['dimensions'], true);
                    $dataAwb['data_flight'] = json_decode($dataAwb['data_flight'], true);
                    $awb->dimensions = json_decode($awb->dimensions, true);
                    $awb->data_flight = json_decode($awb->data_flight, true);
                    $dataJob['id_job'] = $data['id_job'];
                    $dataJob['id_awb'] = $job->id_awb;
                    $insertLog = DB::table('log_job')->insert([
                        'id_job' => $data['id_job'],
                        'action' => json_encode([
                            'action' => 'update',
                            'data' => [

                                'job_after' => $dataJob,
                                'job_before' => $job,
                                'awb_after' => $dataAwb,
                                'awb_before' => $awb,
                            ],
                        ]),
                        'id_user' => $request->user()->id_user,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    if ($insertLog) {
                        DB::commit();
                        return ResponseHelper::success('Job updated successfully.', NULL, 200);
                    } else {
                        throw new Exception('Failed to log job update action.');
                    }
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

    public function excecuteJob(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_job' => 'required|integer|exists:job,id_job',
                'agent' => 'required|integer|exists:customers,id_customer',
                'data_agent' => 'required|integer|exists:data_customer,id_data_customer',
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

            $dataAwb = [
                'id_job' => $request->id_job,
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
            } else {
                throw new Exception('Failed to insert AWB data.');
            }
        } catch (Exception $th) {
            return ResponseHelper::error($th);
        }
    }

   
}
