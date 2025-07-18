<?php

namespace App\Http\Controllers\flow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\flow\JobModel;


class JobController extends Controller
{
    public function updateJob(Request $request)
    {

        DB::beginTransaction();
        try {
            $data = $request->validate([
                'id_job' => 'required|integer|exists:job,id_job',
                'awb' => 'required|string|max:50|exists:awb,awb',
                'agent' => 'required|integer|exists:customers,id_customer',
                'consignee' => 'required|integer|exists:customers,id_customer',
                'date' => 'required|date',
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

            ]);

            $job = DB::table('job')->where('id_job', $data['id_job'])->first();

            $dataJob = [
                'agent' => $data['agent'],
                'consignee' => $data['consignee'],
                'date' => $data['date'],
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
                'handling_instructions' => $data['special_instructions'],
                'created_by' => $request->user()->id_user,
                'updated_at' => now(),
            ];

            $updateJob = DB::table('job')->where('id_job', $data['id_job'])->update($dataJob);
            if ($updateJob) {
                $updateAwb =  DB::table('awb')->where('awb', $data['awb'])->update($dataAwb);
                if ($updateAwb) {
                    $insertLog = DB::table('log_job')->insert([
                        'id_job' => $data['id_job'],
                        'action' => 'Updated job id_job: ' . $data['id_job'] . ' - AWB: ' . $data['awb'],
                        'id_user' => $request->user()->id_user,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    if ($insertLog) {
                        DB::commit();
                        return response()->json([
                            'status' => 'success',
                            'code' => 200,
                            'meta_data' => [
                                'code' => 200,
                                'message' => 'Job updated successfully.',
                            ],

                        ], 200);
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
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'meta_data' => [
                    'code' => 500,
                    'message' => $th->getMessage(),
                ],

            ], 500);
        }
    }

    public function getJob(Request $request)
    {
        try {
            $jobModel = new JobModel();
            $limit = $request->input('limit', 10);
            $search = $request->input('searchKey', '');
            $jobs = $jobModel->getJob($search, $limit);
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => $jobs,
            ], 200);
        } catch (Exception $th) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
