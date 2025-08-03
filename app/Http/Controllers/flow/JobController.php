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

            $updateJob = JobModel::findOrFail($request->id_job);
            $awb = DB::table('awb')->where('id_job', $updateJob->id_job)->first();
            if ($awb) {
               throw new Exception('Job already has an associated AWB. Please update the AWB instead.');
            }
            $updateJob->awb = $request->awb;
            $updateJob->agent = $request->agent;
            $updateJob->data_agent = $request->data_agent;
            $updateJob->etd = $request->etd;
            $updateJob->eta = $request->eta;
            $updateJob->pol = $request->pol;
            $updateJob->pod = $request->pod;
            $updateJob->commodity = $request->commodity;
            $updateJob->gross_weight = $request->gross_weight;
            $updateJob->chargeable_weight = $request->chargeable_weight;
            $updateJob->pieces = $request->pieces;
            $updateJob->special_instructions = $request->special_instructions;
            $updateJob->updated_by = $request->user()->id_user;

            if ($updateJob->save()) {
                // Handle dimensions and flight data if provided
                if (isset($request->dimensions_job) && is_array($request->dimensions_job)) {
                   
                    foreach ($request->dimensions_job  as $dimension) {
                        if (isset($dimension['id_dimensionjob']) && $dimension['id_dimensionjob']) {
                            DB::table('dimension_job')->where('id_dimensionjob', $dimension['id_dimensionjob'])->update([
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
                            DB::table('dimension_job')->insert([
                                'id_job' => $updateJob->id_job,
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

                if (isset($request->flight_job) && is_array($request->flight_job)) {
                    foreach ($request->flight_job as $flight) {
                        if (isset($flight['id_flightjob']) && $flight['id_flightjob']) {
                            DB::table('flight_job')->where('id_flightjob', $flight['id_flightjob'])->update([
                                'flight_number' => $flight['flight_number'],
                                'departure' => $flight['departure'],
                                'departure_timezone' => $flight['departure_timezone'],
                                'arrival' => $flight['arrival'],
                                'arrival_timezone' => $flight['arrival_timezone'],
                                'updated_at' => now(),
                                'updated_by' => $request->user()->id_user,
                            ]);
                        } else {
                            DB::table('flight_job')->insert([
                                'id_job' => $updateJob->id_job,
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
                'id_job' => 'required|integer|exists:job,id_job',
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
}
