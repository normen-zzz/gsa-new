<?php

namespace App\Http\Controllers\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Auth;
date_default_timezone_set('Asia/Jakarta');

class FlowapprovalController extends Controller
{
    public function createFlowApprovalSalesOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request data
            $request->validate([
                'data' => 'required|array|min:1',
                'data.*.request_position' => 'required|integer|exists:positions,id_position',
                'data.*.request_division' => 'required|integer|exists:divisions,id_division',
                'data.*.approval_position' => 'required|integer|exists:positions,id_position',
                'data.*.approval_division' => 'required|integer|exists:divisions,id_division',
                'data.*.step_no' => 'required|integer|min:1|unique:flowapproval_salesorder,step_no',
                'data.*.status' => 'required|in:active,inactive',
                'data.*.next_step' => 'nullable|integer',
            ]);
            
            $insertData = [];
            foreach ($request->data as $data) {
                $insertData[] = [
                    'request_position' => $data['request_position'],
                    'request_division' => $data['request_division'],
                    'approval_position' => $data['approval_position'],
                    'approval_division' => $data['approval_division'],
                    'step_no' => $data['step_no'],
                    'status' => $data['status'],
                    'next_step' => $data['next_step'] ?? null,
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                ];
            }
            
            DB::table('flowapproval_salesorder')->insert($insertData);
            DB::commit();
            return ResponseHelper::success('Flow approval for sales order created successfully', null, 200);
        }
        catch (Exception $e) {
            return ResponseHelper::error($e);
        }
    }

    public function getFlowApprovalSalesOrder(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');

        $query = DB::table('flowapproval_salesorder')
            ->select('id_flowapproval_salesorder', 'request_position', 'request_division', 'approval_position', 'approval_division', 'step_no', 'status', 'next_step', 'created_at')
            ->where(function ($q) use ($search) {
                $q->where('request_position', 'like', '%' . $search . '%')
                  ->orWhere('approval_position', 'like', '%' . $search . '%');
            })
            ->orderBy('step_no', 'asc');

        $flowApprovals = $query->paginate($limit);

        return ResponseHelper::success('Flow approvals for sales order retrieved successfully.', $flowApprovals, 200);
    }

    public function updateFlowApprovalSalesOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_salesorder');
            $request->validate([
                'id_flowapproval_salesorder' => 'required|integer|exists:flowapproval_salesorder,id_flowapproval_salesorder',
                'request_position' => 'required|integer|exists:positions,id_position',
                'request_division' => 'required|integer|exists:divisions,id_division',
                'approval_position' => 'required|integer|exists:positions,id_position',
                'approval_division' => 'required|integer|exists:divisions,id_division',
                'step_no' => 'required|integer|min:1|unique:flowapproval_salesorder,step_no,' . $id,
                'status' => 'required|in:active,inactive',
                'next_step' => 'nullable|integer',
            ]);

            DB::table('flowapproval_salesorder')
                ->where('id_flowapproval_salesorder', $id)
                ->update([
                    'request_position' => $request->input('request_position'),
                    'request_division' => $request->input('request_division'),
                    'approval_position' => $request->input('approval_position'),
                    'approval_division' => $request->input('approval_division'),
                    'step_no' => $request->input('step_no'),
                    'status' => $request->input('status'),
                    'next_step' => $request->input('next_step'),
                    'updated_at' => now(),
                ]);

            DB::commit();
            return ResponseHelper::success('Flow approval for sales order updated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteFlowApprovalSalesOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_salesorder');
            $request->validate([
                'id_flowapproval_salesorder' => 'required|integer|exists:flowapproval_salesorder,id_flowapproval_salesorder',
            ]);

            DB::table('flowapproval_salesorder')
                ->where('id_flowapproval_salesorder', $id)
                ->delete();

            DB::commit();
            return ResponseHelper::success('Flow approval for sales order deleted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }


}
