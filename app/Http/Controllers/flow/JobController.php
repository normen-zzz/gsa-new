<?php

namespace App\Http\Controllers\flow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobController extends Controller
{
    public function updateJob(Request $request)
    {
        $data = $request->validate([
            'id_job' => 'required|integer|exists:job,id_job',
            'agent' => 'required|integer|exists:customers,id_customer',
            'consignee' => 'required|integer|exists:customers,id_customer',
            'date' => 'required|date',
            'etd' => 'required|date',
            'eta' => 'required|date',
        ]);

        $data['updated_by'] = $request->user()->id_user;
        $data['updated_at'] = now();

        DB::beginTransaction();
        try {
            $updateJob = DB::table('job')
                ->where('id_job', $data['id_job'])
                ->update($data);

            if ($updateJob) {
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Job updated successfully.',
                ], 200);
            } else {
                throw new \Exception('Failed to update job.');
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
