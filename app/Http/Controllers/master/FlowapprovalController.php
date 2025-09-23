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
                'request_position' => 'required|integer|exists:positions,id_position',
                'request_division' => 'required|integer|exists:divisions,id_division',
                'data' => 'nullable|array',
                'data.*.approval_position' => 'required|integer|exists:positions,id_position',
                'data.*.approval_division' => 'required|integer|exists:divisions,id_division',
                'data.*.step_no' => 'required|integer|min:1',
                'data.*.status' => 'required|in:active,inactive',
            ]);

            // $checkRequestPosition = DB::table('positions')
            //     ->where('id_position', $request->request_position)
            //     ->exists();

            // $checkRequestDivision = DB::table('divisions')
            //     ->where('id_division', $request->request_division)
            //     ->exists();

            // if (!$checkRequestPosition) {
            //     throw new Exception('Invalid request position');
            // }
            // if (!$checkRequestDivision) {
            //     throw new Exception('Invalid request division');
            // }

            $flowApproval = [
                'request_position' => $request->request_position,
                'request_division' => $request->request_division,
                'status' => 'active',
                'created_by' => Auth::id(),
                'created_at' => now()
            ];
            $insertFlowapproval = DB::table('flowapproval_salesorder')->insertGetId($flowApproval);
            if ($insertFlowapproval) {
                foreach ($request->data as $data) {
                    // $checkApprovalPosition = DB::table('positions')
                    //     ->where('id_position', $data['approval_position'])
                    //     ->exists();

                    // if (!$checkApprovalPosition) {
                    //     throw new Exception('Invalid approval position');
                    // }
                    // $checkApprovalDivision = DB::table('divisions')
                    //     ->where('id_division', $data['approval_division'])
                    //     ->exists();

                    // if (!$checkApprovalDivision) {
                    //     throw new Exception('Invalid approval division');
                    // }

                    $dataDetailflowapproval = [
                        'id_flowapproval_salesorder' => $insertFlowapproval,
                        'approval_position' => $data['approval_position'],
                        'approval_division' => $data['approval_division'],
                        'step_no' => $data['step_no'],
                        'status' => $data['status'],
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ];
                    $insertDetailflowapproval =  DB::table('detailflowapproval_salesorder')->insert($dataDetailflowapproval);
                    if (!$insertDetailflowapproval) {
                        throw new Exception('Failed to insert detail flow approval');
                    }
                }
            } else {
                throw new Exception('Failed to insert flow approval');
            }


            DB::commit();
            return ResponseHelper::success('Flow approval for sales order created successfully', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::error($e);
        }
    }

    public function getFlowApprovalSalesOrder(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');

        $select = [
            'a.id_flowapproval_salesorder',
            'a.request_position',
            'c.name AS request_position_name',
            'a.request_division',
            'd.name AS request_division_name',

            'a.status',
            'a.created_at',
            'a.created_by',
            'b.name AS created_by_name'
        ];

        $flowApprovals = DB::table('flowapproval_salesorder AS a')
            ->select($select)
            ->join('users AS b', 'a.created_by', '=', 'b.id_user')
            ->join('positions AS c', 'a.request_position', '=', 'c.id_position')
            ->join('divisions AS d', 'a.request_division', '=', 'd.id_division')
            ->where(function ($q) use ($search) {
                $q->where('c.name', 'like', '%' . $search . '%')
                    ->orWhere('d.name', 'like', '%' . $search . '%');
            })
            ->paginate($limit);

        $flowApprovals->transform(function ($item) {
            $selectDetailflowapproval = [
                'a.id_detailflowapproval_salesorder',
                'a.id_flowapproval_salesorder',
                'a.approval_position',
                'b.name AS approval_position_name',
                'a.approval_division',
                'c.name AS approval_division_name',
                'a.step_no',
                'a.status',
                'a.created_at',
                'd.name AS created_by_name',
                'a.created_by'
            ];
            $detailFlowapproval = DB::table('detailflowapproval_salesorder AS a')
                ->select($selectDetailflowapproval)
                ->join('positions AS b', 'a.approval_position', '=', 'b.id_position')
                ->join('divisions AS c', 'a.approval_division', '=', 'c.id_division')
                ->join('users AS d', 'a.created_by', '=', 'd.id_user')
                ->where('id_flowapproval_salesorder', $item->id_flowapproval_salesorder)
                ->get();
            $item->detail_flowapproval = $detailFlowapproval;
            return $item;
        });

        return ResponseHelper::success('Flow approvals for sales order retrieved successfully.', $flowApprovals, 200);
    }

    public function updateFlowApprovalSalesOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_salesorder');
            $flowapproval_salesorder = DB::table('flowapproval_salesorder')->where('id_flowapproval_salesorder', $id)->first();
            $request->validate([
                'id_flowapproval_salesorder' => 'required|integer|exists:flowapproval_salesorder,id_flowapproval_salesorder',
                'request_position' => 'required|integer|exists:positions,id_position',
                'request_division' => 'required|integer|exists:divisions,id_division',
                'status' => 'required|in:active,inactive',
                'detail_flowapproval' => 'required|array',
                'detail_flowapproval.*.id_detailflowapproval_salesorder' => 'nullable|integer|exists:detailflowapproval_salesorder,id_detailflowapproval_salesorder',
                'detail_flowapproval.*.approval_position' => 'required|integer|exists:positions,id_position',
                'detail_flowapproval.*.approval_division' => 'required|integer|exists:divisions,id_division',
                'detail_flowapproval.*.step_no' => 'required|integer|min:1|distinct',
                'detail_flowapproval.*.status' => 'required|in:active,inactive',
            ]);

            $changesFlowapproval = [];
            $changesDetailflowapproval = [];

            $checkRequestPosition = DB::table('positions')
                ->where('id_position', $request->request_position)
                ->exists();
                if (!$checkRequestPosition) {
                    throw new Exception('Invalid request position');
                }

            $checkRequestDivision = DB::table('divisions')
                ->where('id_division', $request->request_division)
                ->exists();
                if (!$checkRequestDivision) {
                    throw new Exception('Invalid request division');
                }

            $updateFlowapproval = DB::table('flowapproval_salesorder')
                ->where('id_flowapproval_salesorder', $id)
                ->update([
                    'request_position' => $request->input('request_position'),
                    'request_division' => $request->input('request_division'),
                    'status' => $request->input('status'),
                    'updated_at' => now(),
                ]);
            


            if ($request->has('detail_flowapproval')) {
                foreach ($request->input('detail_flowapproval') as $detail) {
                    if (isset($detail['id_detailflowapproval_salesorder']) || $detail['id_detailflowapproval_salesorder'] != NULL) {
                        // Update existing detail
                        $detailFlowApproval = DB::table('detailflowapproval_salesorder')->where('id_detailflowapproval_salesorder', $detail['id_detailflowapproval_salesorder'])->first();
                        $insertDetailflowapproval =  DB::table('detailflowapproval_salesorder')
                            ->where('id_detailflowapproval_salesorder', $detail['id_detailflowapproval_salesorder'])
                            ->update([
                                'approval_position' => $detail['approval_position'],
                                'approval_division' => $detail['approval_division'],
                                'step_no' => $detail['step_no'],
                                'status' => $detail['status'],
                                'updated_at' => now(),
                            ]);
                        if ($insertDetailflowapproval) {
                            // Log the update action
                            DB::table('log_flowapproval_salesorder')->insert([
                                'id_flowapproval_salesorder' => $detail['id_flowapproval_salesorder'],
                                'action' => json_encode([
                                    'type' => 'updated',
                                    'data' => [
                                        'id_detailflowapproval_salesorder' => $detail['id_detailflowapproval_salesorder'],
                                        'from' => $detailFlowApproval,
                                        'to' => $detail
                                    ]
                                ]),
                                'created_by' => Auth::id(),
                                'created_at' => now(),
                            ]);
                        }
                    } else {
                        // Insert new detail
                        DB::table('detailflowapproval_salesorder')->insert([
                            'id_flowapproval_salesorder' => $id,
                            'approval_position' => $detail['approval_position'],
                            'approval_division' => $detail['approval_division'],
                            'step_no' => $detail['step_no'],
                            'status' => $detail['status'],
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ]);
                    }
                }
            }

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
                ->update([
                    'status' => 'inactive',
                    'updated_at' => now(),
                ]);

            $changes = [
                'type' => 'deactivated',
            ];
            DB::table('log_flowapproval_salesorder')->insert([
                'id_flowapproval_salesorder' => $id,
                'action' => json_encode($changes),
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            DB::commit();
            return ResponseHelper::success('Flow approval for sales order deleted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function activateFlowApprovalSalesOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_salesorder');
            $request->validate([
                'id_flowapproval_salesorder' => 'required|integer|exists:flowapproval_salesorder,id_flowapproval_salesorder',
            ]);

            DB::table('flowapproval_salesorder')
                ->where('id_flowapproval_salesorder', $id)
                ->update([
                    'status' => 'active',
                    'updated_at' => now(),
                ]);

            $changes = [
                'type' => 'activated',
            ];

            DB::table('log_flowapproval_salesorder')->insert([
                'id_flowapproval_salesorder' => $id,
                'action' => json_encode($changes),
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            DB::commit();
            return ResponseHelper::success('Flow approval for sales order activated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    // jobsheet

    public function createFlowApprovalJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request data
            $request->validate([
                'request_position' => 'required|integer|exists:positions,id_position',
                'request_division' => 'required|integer|exists:divisions,id_division',
                'data' => 'nullable|array',
                'data.*.approval_position' => 'required|integer|exists:positions,id_position',
                'data.*.approval_division' => 'required|integer|exists:divisions,id_division',
                'data.*.step_no' => 'required|integer|min:1',
                'data.*.status' => 'required|in:active,inactive',
            ]);

            

            $flowApproval = [
                'request_position' => $request->request_position,
                'request_division' => $request->request_division,
                'status' => 'active',
                'created_by' => Auth::id(),
                'created_at' => now()
            ];
            $insertFlowapproval = DB::table('flowapproval_jobsheet')->insertGetId($flowApproval);
            if ($insertFlowapproval) {
                foreach ($request->data as $data) {
                    

                    $dataDetailflowapproval = [
                        'id_flowapproval_jobsheet' => $insertFlowapproval,
                        'approval_position' => $data['approval_position'],
                        'approval_division' => $data['approval_division'],
                        'step_no' => $data['step_no'],
                        'status' => $data['status'],
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ];
                    $insertDetailflowapproval =  DB::table('detailflowapproval_jobsheet')->insert($dataDetailflowapproval);
                    if (!$insertDetailflowapproval) {
                        throw new Exception('Failed to insert detail flow approval');
                    }
                }
            } else {
                throw new Exception('Failed to insert flow approval');
            }


            DB::commit();
            return ResponseHelper::success('Flow approval for jobsheet created successfully', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::error($e);
        }
    }

    public function getFlowApprovalJobsheet(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');

        $select = [
            'a.id_flowapproval_jobsheet',
            'a.request_position',
            'c.name AS request_position_name',
            'a.request_division',
            'd.name AS request_division_name',

            'a.status',
            'a.created_at',
            'a.created_by',
            'b.name AS created_by_name'
        ];

        $flowApprovals = DB::table('flowapproval_jobsheet AS a')
            ->select($select)
            ->join('users AS b', 'a.created_by', '=', 'b.id_user')
            ->join('positions AS c', 'a.request_position', '=', 'c.id_position')
            ->join('divisions AS d', 'a.request_division', '=', 'd.id_division')
            ->where(function ($q) use ($search) {
                $q->where('c.name', 'like', '%' . $search . '%')
                    ->orWhere('d.name', 'like', '%' . $search . '%');
            })
            ->paginate($limit);

        $flowApprovals->transform(function ($item) {
            $selectDetailflowapproval = [
                'a.id_detailflowapproval_jobsheet',
                'a.id_flowapproval_jobsheet',
                'a.approval_position',
                'b.name AS approval_position_name',
                'a.approval_division',
                'c.name AS approval_division_name',
                'a.step_no',
                'a.status',
                'a.created_at',
                'd.name AS created_by_name',
                'a.created_by'
            ];
            $detailFlowapproval = DB::table('detailflowapproval_jobsheet AS a')
                ->select($selectDetailflowapproval)
                ->join('positions AS b', 'a.approval_position', '=', 'b.id_position')
                ->join('divisions AS c', 'a.approval_division', '=', 'c.id_division')
                ->join('users AS d', 'a.created_by', '=', 'd.id_user')
                ->where('id_flowapproval_jobsheet', $item->id_flowapproval_jobsheet)
                ->get();
            $item->detail_flowapproval = $detailFlowapproval;
            return $item;
        });

        return ResponseHelper::success('Flow approvals for jobsheet retrieved successfully.', $flowApprovals, 200);
    }

    public function updateFlowApprovalJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_jobsheet');
            $flowapproval_jobsheet = DB::table('flowapproval_jobsheet')->where('id_flowapproval_jobsheet', $id)->first();
            $request->validate([
                'id_flowapproval_jobsheet' => 'required|integer|exists:flowapproval_jobsheet,id_flowapproval_jobsheet',
                'request_position' => 'required|integer|exists:positions,id_position',
                'request_division' => 'required|integer|exists:divisions,id_division',
                'status' => 'required|in:active,inactive',
                'detail_flowapproval' => 'required|array',
                'detail_flowapproval.*.id_detailflowapproval_jobsheet' => 'nullable|integer|exists:detailflowapproval_jobsheet,id_detailflowapproval_jobsheet',
                'detail_flowapproval.*.approval_position' => 'required|integer|exists:positions,id_position',
                'detail_flowapproval.*.approval_division' => 'required|integer|exists:divisions,id_division',
                'detail_flowapproval.*.step_no' => 'required|integer|min:1|distinct',
                'detail_flowapproval.*.status' => 'required|in:active,inactive',
            ]);

            $changesFlowapproval = [];
            $changesDetailflowapproval = [];

            $checkRequestPosition = DB::table('positions')
                ->where('id_position', $request->request_position)
                ->exists();
                if (!$checkRequestPosition) {
                    throw new Exception('Invalid request position');
                }

            $checkRequestDivision = DB::table('divisions')
                ->where('id_division', $request->request_division)
                ->exists();
                if (!$checkRequestDivision) {
                    throw new Exception('Invalid request division');
                }

            $updateFlowapproval = DB::table('flowapproval_jobsheet')
                ->where('id_flowapproval_jobsheet', $id)
                ->update([
                    'request_position' => $request->input('request_position'),
                    'request_division' => $request->input('request_division'),
                    'status' => $request->input('status'),
                    'updated_at' => now(),
                ]);
            


            if ($request->has('detail_flowapproval')) {
                foreach ($request->input('detail_flowapproval') as $detail) {
                    if (isset($detail['id_detailflowapproval_jobsheet']) || $detail['id_detailflowapproval_jobsheet'] != NULL) {
                        // Update existing detail
                        $insertDetailflowapproval =  DB::table('detailflowapproval_jobsheet')
                            ->where('id_detailflowapproval_jobsheet', $detail['id_detailflowapproval_jobsheet'])
                            ->update([
                                'approval_position' => $detail['approval_position'],
                                'approval_division' => $detail['approval_division'],
                                'step_no' => $detail['step_no'],
                                'status' => $detail['status'],
                                'updated_at' => now(),
                            ]);
                        if ($insertDetailflowapproval) {
                            // Log the update action
                            DB::table('log_flowapproval_jobsheet')->insert([
                                'id_flowapproval_jobsheet' => $id,
                                'action' => json_encode([
                                    'id_detailflowapproval_jobsheet' => $detail['id_detailflowapproval_jobsheet'],
                                    'type' => 'updated',
                                    'data' => $detail
                                ]),
                                'created_by' => Auth::id(),
                                'created_at' => now(),
                            ]);
                        }
                    } else {
                        // Insert new detail
                        $insert= DB::table('detailflowapproval_jobsheet')->insert([
                            'id_flowapproval_jobsheet' => $id,
                            'approval_position' => $detail['approval_position'],
                            'approval_division' => $detail['approval_division'],
                            'step_no' => $detail['step_no'],
                            'status' => $detail['status'],
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ]);
                        $log = [
                            'id_detailflowapproval_jobsheet' => DB::getPdo()->lastInsertId(),
                            'type' => 'insert',
                            'old' => null,
                            'new' => $insert
                        ];
                        DB::table('log_flowapproval_jobsheet')->insert([
                            'id_flowapproval_jobsheet' => $id,
                            'action' => json_encode($log),
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            DB::commit();
            return ResponseHelper::success('Flow approval for jobsheet updated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteFlowApprovalJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_jobsheet');
            $request->validate([
                'id_flowapproval_jobsheet' => 'required|integer|exists:flowapproval_jobsheet,id_flowapproval_jobsheet',
            ]);

            DB::table('flowapproval_jobsheet')
                ->where('id_flowapproval_jobsheet', $id)
                ->update([
                    'status' => 'inactive',
                    'updated_at' => now(),
                ]);

            $changes = [
                'type' => 'deactivated',
            ];
            DB::table('log_flowapproval_jobsheet')->insert([
                'id_flowapproval_jobsheet' => $id,
                'action' => json_encode($changes),
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            DB::commit();
            return ResponseHelper::success('Flow approval for jobsheet deleted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function activateFlowApprovalJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_jobsheet');
            $request->validate([
                'id_flowapproval_jobsheet' => 'required|integer|exists:flowapproval_jobsheet,id_flowapproval_jobsheet',
            ]);

            DB::table('flowapproval_jobsheet')
                ->where('id_flowapproval_jobsheet', $id)
                ->update([
                    'status' => 'active',
                    'updated_at' => now(),
                ]);

            $changes = [
                'type' => 'activated',
            ];

            DB::table('log_flowapproval_jobsheet')->insert([
                'id_flowapproval_jobsheet' => $id,
                'action' => json_encode($changes),
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            DB::commit();
            return ResponseHelper::success('Flow approval for jobsheet activated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    //invoice

     public function createFlowApprovalInvoice(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request data
            $request->validate([
                'request_position' => 'required|integer|exists:positions,id_position',
                'request_division' => 'required|integer|exists:divisions,id_division',
                'data' => 'nullable|array',
                'data.*.approval_position' => 'required|integer|exists:positions,id_position',
                'data.*.approval_division' => 'required|integer|exists:divisions,id_division',
                'data.*.step_no' => 'required|integer|min:1',
                'data.*.status' => 'required|in:active,inactive',
            ]);

            

            $flowApproval = [
                'request_position' => $request->request_position,
                'request_division' => $request->request_division,
                'status' => 'active',
                'created_by' => Auth::id(),
                'created_at' => now()
            ];
            $insertFlowapproval = DB::table('flowapproval_invoice')->insertGetId($flowApproval);
            if ($insertFlowapproval) {
                foreach ($request->data as $data) {
                    

                    $dataDetailflowapproval = [
                        'id_flowapproval_invoice' => $insertFlowapproval,
                        'approval_position' => $data['approval_position'],
                        'approval_division' => $data['approval_division'],
                        'step_no' => $data['step_no'],
                        'status' => $data['status'],
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ];
                    $insertDetailflowapproval =  DB::table('detailflowapproval_invoice')->insert($dataDetailflowapproval);
                    if (!$insertDetailflowapproval) {
                        throw new Exception('Failed to insert detail flow approval invoice');
                    }
                }
            } else {
                throw new Exception('Failed to insert flow approval invoice');
            }


            DB::commit();
            return ResponseHelper::success('Flow approval for invoice created successfully', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::error($e);
        }
    }

    public function getFlowApprovalInvoice(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');

        $select = [
            'a.id_flowapproval_invoice',
            'a.request_position',
            'c.name AS request_position_name',
            'a.request_division',
            'd.name AS request_division_name',

            'a.status',
            'a.created_at',
            'a.created_by',
            'b.name AS created_by_name'
        ];

        $flowApprovals = DB::table('flowapproval_invoice AS a')
            ->select($select)
            ->join('users AS b', 'a.created_by', '=', 'b.id_user')
            ->join('positions AS c', 'a.request_position', '=', 'c.id_position')
            ->join('divisions AS d', 'a.request_division', '=', 'd.id_division')
            ->where(function ($q) use ($search) {
                $q->where('c.name', 'like', '%' . $search . '%')
                    ->orWhere('d.name', 'like', '%' . $search . '%');
            })
            ->paginate($limit);

        $flowApprovals->transform(function ($item) {
            $selectDetailflowapproval = [
                'a.id_detailflowapproval_invoice',
                'a.id_flowapproval_invoice',
                'a.approval_position',
                'b.name AS approval_position_name',
                'a.approval_division',
                'c.name AS approval_division_name',
                'a.step_no',
                'a.status',
                'a.created_at',
                'd.name AS created_by_name',
                'a.created_by'
            ];
            $detailFlowapproval = DB::table('detailflowapproval_invoice AS a')
                ->select($selectDetailflowapproval)
                ->join('positions AS b', 'a.approval_position', '=', 'b.id_position')
                ->join('divisions AS c', 'a.approval_division', '=', 'c.id_division')
                ->join('users AS d', 'a.created_by', '=', 'd.id_user')
                ->where('id_flowapproval_invoice', $item->id_flowapproval_invoice)
                ->get();
            $item->detail_flowapproval = $detailFlowapproval;
            return $item;
        });

        return ResponseHelper::success('Flow approvals for invoice retrieved successfully.', $flowApprovals, 200);
    }

    public function updateFlowApprovalInvoice(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_invoice');
            $flowapproval_invoice = DB::table('flowapproval_invoice')->where('id_flowapproval_invoice', $id)->first();
            $request->validate([
                'id_flowapproval_invoice' => 'required|integer|exists:flowapproval_invoice,id_flowapproval_invoice',
                'request_position' => 'required|integer|exists:positions,id_position',
                'request_division' => 'required|integer|exists:divisions,id_division',
                'status' => 'required|in:active,inactive',
                'detail_flowapproval' => 'required|array',
                'detail_flowapproval.*.id_detailflowapproval_invoice' => 'nullable|integer|exists:detailflowapproval_invoice,id_detailflowapproval_invoice',
                'detail_flowapproval.*.approval_position' => 'required|integer|exists:positions,id_position',
                'detail_flowapproval.*.approval_division' => 'required|integer|exists:divisions,id_division',
                'detail_flowapproval.*.step_no' => 'required|integer|min:1|distinct',
                'detail_flowapproval.*.status' => 'required|in:active,inactive',
            ]);

            $changesFlowapproval = [];
            $changesDetailflowapproval = [];

            $checkRequestPosition = DB::table('positions')
                ->where('id_position', $request->request_position)
                ->exists();
                if (!$checkRequestPosition) {
                    throw new Exception('Invalid request position');
                }

            $checkRequestDivision = DB::table('divisions')
                ->where('id_division', $request->request_division)
                ->exists();
                if (!$checkRequestDivision) {
                    throw new Exception('Invalid request division');
                }

            $updateFlowapproval = DB::table('flowapproval_invoice')
                ->where('id_flowapproval_invoice', $id)
                ->update([
                    'request_position' => $request->input('request_position'),
                    'request_division' => $request->input('request_division'),
                    'status' => $request->input('status'),
                    'updated_at' => now(),
                ]);
            


            if ($request->has('detail_flowapproval')) {
                foreach ($request->input('detail_flowapproval') as $detail) {
                    if (isset($detail['id_detailflowapproval_invoice']) || $detail['id_detailflowapproval_invoice'] != NULL) {
                        // Update existing detail
                        $insertDetailflowapproval =  DB::table('detailflowapproval_invoice')
                            ->where('id_detailflowapproval_invoice', $detail['id_detailflowapproval_invoice'])
                            ->update([
                                'approval_position' => $detail['approval_position'],
                                'approval_division' => $detail['approval_division'],
                                'step_no' => $detail['step_no'],
                                'status' => $detail['status'],
                                'updated_at' => now(),
                            ]);
                        if ($insertDetailflowapproval) {
                            // Log the update action
                            DB::table('log_flowapproval_invoice')->insert([
                                'id_flowapproval_invoice' => $id,
                                'action' => json_encode([
                                    'id_detailflowapproval_invoice' => $detail['id_detailflowapproval_invoice'],
                                    'type' => 'updated',
                                    'data' => $detail
                                ]),
                                'created_by' => Auth::id(),
                                'created_at' => now(),
                            ]);
                        }
                    } else {
                        // Insert new detail
                        $insert= DB::table('detailflowapproval_invoice')->insert([
                            'id_flowapproval_invoice' => $id,
                            'approval_position' => $detail['approval_position'],
                            'approval_division' => $detail['approval_division'],
                            'step_no' => $detail['step_no'],
                            'status' => $detail['status'],
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ]);
                        $log = [
                            'id_detailflowapproval_invoice' => DB::getPdo()->lastInsertId(),
                            'type' => 'insert',
                            'old' => null,
                            'new' => $insert
                        ];
                        DB::table('log_flowapproval_invoice')->insert([
                            'id_flowapproval_invoice' => $id,
                            'action' => json_encode($log),
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            DB::commit();
            return ResponseHelper::success('Flow approval for invoice updated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteFlowApprovalInvoice(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_invoice');
            $request->validate([
                'id_flowapproval_invoice' => 'required|integer|exists:flowapproval_invoice,id_flowapproval_invoice',
            ]);

            DB::table('flowapproval_invoice')
                ->where('id_flowapproval_invoice', $id)
                ->update([
                    'status' => 'inactive',
                    'updated_at' => now(),
                ]);

            $changes = [
                'type' => 'deactivated',
            ];
            DB::table('log_flowapproval_invoice')->insert([
                'id_flowapproval_invoice' => $id,
                'action' => json_encode($changes),
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            DB::commit();
            return ResponseHelper::success('Flow approval for invoice deleted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function activateFlowApprovalInvoice(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_invoice');
            $request->validate([
                'id_flowapproval_invoice' => 'required|integer|exists:flowapproval_invoice,id_flowapproval_invoice',
            ]);

            DB::table('flowapproval_invoice')
                ->where('id_flowapproval_invoice', $id)
                ->update([
                    'status' => 'active',
                    'updated_at' => now(),
                ]);

            $changes = [
                'type' => 'activated',
            ];

            DB::table('log_flowapproval_invoice')->insert([
                'id_flowapproval_invoice' => $id,
                'action' => json_encode($changes),
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            DB::commit();
            return ResponseHelper::success('Flow approval for invoice activated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }


    //account payable

 public function createFlowApprovalAccountpayable(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request data
            $request->validate([
                'request_position' => 'required|integer|exists:positions,id_position',
                'request_division' => 'required|integer|exists:divisions,id_division',
                'data' => 'nullable|array',
                'data.*.approval_position' => 'required|integer|exists:positions,id_position',
                'data.*.approval_division' => 'required|integer|exists:divisions,id_division',
                'data.*.step_no' => 'required|integer|min:1',
                'data.*.status' => 'required|in:active,inactive',
            ]);

            

            $flowApproval = [
                'request_position' => $request->request_position,
                'request_division' => $request->request_division,
                'status' => 'active',
                'created_by' => Auth::id(),
                'created_at' => now()
            ];
            $insertFlowapproval = DB::table('flowapproval_accountpayable')->insertGetId($flowApproval);
            if ($insertFlowapproval) {
                foreach ($request->data as $data) {
                    

                    $dataDetailflowapproval = [
                        'id_flowapproval_accountpayable' => $insertFlowapproval,
                        'approval_position' => $data['approval_position'],
                        'approval_division' => $data['approval_division'],
                        'step_no' => $data['step_no'],
                        'status' => $data['status'],
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ];
                    $insertDetailflowapproval =  DB::table('detailflowapproval_accountpayable')->insert($dataDetailflowapproval);
                    if (!$insertDetailflowapproval) {
                        throw new Exception('Failed to insert detail flow approval account payable');
                    }
                }
            } else {
                throw new Exception('Failed to insert flow approval account payable');
            }


            DB::commit();
            return ResponseHelper::success('Flow approval for account payable created successfully', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::error($e);
        }
    }

    public function getFlowApprovalAccountpayable(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');

        $select = [
            'a.id_flowapproval_accountpayable',
            'a.request_position',
            'c.name AS request_position_name',
            'a.request_division',
            'd.name AS request_division_name',

            'a.status',
            'a.created_at',
            'a.created_by',
            'b.name AS created_by_name'
        ];

        $flowApprovals = DB::table('flowapproval_accountpayable AS a')
            ->select($select)
            ->join('users AS b', 'a.created_by', '=', 'b.id_user')
            ->join('positions AS c', 'a.request_position', '=', 'c.id_position')
            ->join('divisions AS d', 'a.request_division', '=', 'd.id_division')
            ->where(function ($q) use ($search) {
                $q->where('c.name', 'like', '%' . $search . '%')
                    ->orWhere('d.name', 'like', '%' . $search . '%');
            })
            ->paginate($limit);

        $flowApprovals->transform(function ($item) {
            $selectDetailflowapproval = [
                'a.id_detailflowapproval_accountpayable',
                'a.id_flowapproval_accountpayable',
                'a.approval_position',
                'b.name AS approval_position_name',
                'a.approval_division',
                'c.name AS approval_division_name',
                'a.step_no',
                'a.status',
                'a.created_at',
                'd.name AS created_by_name',
                'a.created_by'
            ];
            $detailFlowapproval = DB::table('detailflowapproval_accountpayable AS a')
                ->select($selectDetailflowapproval)
                ->join('positions AS b', 'a.approval_position', '=', 'b.id_position')
                ->join('divisions AS c', 'a.approval_division', '=', 'c.id_division')
                ->join('users AS d', 'a.created_by', '=', 'd.id_user')
                ->where('id_flowapproval_accountpayable', $item->id_flowapproval_accountpayable)
                ->get();
            $item->detail_flowapproval = $detailFlowapproval;
            return $item;
        });

        return ResponseHelper::success('Flow approvals for account payable retrieved successfully.', $flowApprovals, 200);
    }

    public function updateFlowApprovalAccountpayable(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_accountpayable');
            $flowapproval_accountpayable = DB::table('flowapproval_accountpayable')->where('id_flowapproval_accountpayable', $id)->first();
            $request->validate([
                'id_flowapproval_accountpayable' => 'required|integer|exists:flowapproval_accountpayable,id_flowapproval_accountpayable',
                'request_position' => 'required|integer|exists:positions,id_position',
                'request_division' => 'required|integer|exists:divisions,id_division',
                'status' => 'required|in:active,inactive',
                'detail_flowapproval' => 'required|array',
                'detail_flowapproval.*.id_detailflowapproval_accountpayable' => 'nullable|integer|exists:detailflowapproval_accountpayable,id_detailflowapproval_accountpayable',
                'detail_flowapproval.*.approval_position' => 'required|integer|exists:positions,id_position',
                'detail_flowapproval.*.approval_division' => 'required|integer|exists:divisions,id_division',
                'detail_flowapproval.*.step_no' => 'required|integer|min:1|distinct',
                'detail_flowapproval.*.status' => 'required|in:active,inactive',
            ]);

            $changesFlowapproval = [];
            $changesDetailflowapproval = [];

            $checkRequestPosition = DB::table('positions')
                ->where('id_position', $request->request_position)
                ->exists();
                if (!$checkRequestPosition) {
                    throw new Exception('Invalid request position');
                }

            $checkRequestDivision = DB::table('divisions')
                ->where('id_division', $request->request_division)
                ->exists();
                if (!$checkRequestDivision) {
                    throw new Exception('Invalid request division');
                }

            $updateFlowapproval = DB::table('flowapproval_invoice')
                ->where('id_flowapproval_invoice', $id)
                ->update([
                    'request_position' => $request->input('request_position'),
                    'request_division' => $request->input('request_division'),
                    'status' => $request->input('status'),
                    'updated_at' => now(),
                ]);
            


            if ($request->has('detail_flowapproval')) {
                foreach ($request->input('detail_flowapproval') as $detail) {
                    if (isset($detail['id_detailflowapproval_accountpayable']) || $detail['id_detailflowapproval_accountpayable'] != NULL) {
                        // Update existing detail
                        $insertDetailflowapproval =  DB::table('detailflowapproval_accountpayable')
                            ->where('id_detailflowapproval_accountpayable', $detail['id_detailflowapproval_accountpayable'])
                            ->update([
                                'approval_position' => $detail['approval_position'],
                                'approval_division' => $detail['approval_division'],
                                'step_no' => $detail['step_no'],
                                'status' => $detail['status'],
                                'updated_at' => now(),
                            ]);
                        if ($insertDetailflowapproval) {
                            // Log the update action
                            DB::table('log_flowapproval_accountpayable')->insert([
                                'id_flowapproval_accountpayable' => $id,
                                'action' => json_encode([
                                    'id_detailflowapproval_accountpayable' => $detail['id_detailflowapproval_accountpayable'],
                                    'type' => 'updated',
                                    'data' => $detail
                                ]),
                                'created_by' => Auth::id(),
                                'created_at' => now(),
                            ]);
                        }
                    } else {
                        // Insert new detail
                        $insert= DB::table('detailflowapproval_accountpayable')->insert([
                            'id_flowapproval_accountpayable' => $id,
                            'approval_position' => $detail['approval_position'],
                            'approval_division' => $detail['approval_division'],
                            'step_no' => $detail['step_no'],
                            'status' => $detail['status'],
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ]);
                        $log = [
                            'id_detailflowapproval_accountpayable' => DB::getPdo()->lastInsertId(),
                            'type' => 'insert',
                            'old' => null,
                            'new' => $insert
                        ];
                        DB::table('log_flowapproval_accountpayable')->insert([
                            'id_flowapproval_invoice' => $id,
                            'action' => json_encode($log),
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            DB::commit();
            return ResponseHelper::success('Flow approval for account payable updated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteFlowApprovalAccountPayable(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_accountpayable');
            $request->validate([
                'id_flowapproval_accountpayable' => 'required|integer|exists:flowapproval_accountpayable,id_flowapproval_accountpayable',
            ]);

            DB::table('flowapproval_accountpayable')
                ->where('id_flowapproval_accountpayable', $id)
                ->update([
                    'status' => 'inactive',
                    'updated_at' => now(),
                ]);

            $changes = [
                'type' => 'deactivated',
            ];
            DB::table('log_flowapproval_accountpayable')->insert([
                'id_flowapproval_accountpayable' => $id,
                'action' => json_encode($changes),
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            DB::commit();
            return ResponseHelper::success('Flow approval for account payable deleted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function activateFlowApprovalAccountPayable(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_accountpayable');
            $request->validate([
                'id_flowapproval_accountpayable' => 'required|integer|exists:flowapproval_accountpayable,id_flowapproval_accountpayable',
            ]);

            DB::table('flowapproval_accountpayable')
                ->where('id_flowapproval_accountpayable', $id)
                ->update([
                    'status' => 'active',
                    'updated_at' => now(),
                ]);

            $changes = [
                'type' => 'activated',
            ];

            DB::table('log_flowapproval_accountpayable')->insert([
                'id_flowapproval_accountpayable' => $id,
                'action' => json_encode($changes),
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            DB::commit();
            return ResponseHelper::success('Flow approval for account payable activated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }


    //revisi salesorder

    public function createFlowApprovalRevisiSalesOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request data
            $request->validate([
                'request_position' => 'required|integer|exists:positions,id_position',
                'request_division' => 'required|integer|exists:divisions,id_division',
                'data' => 'nullable|array',
                'data.*.approval_position' => 'required|integer|exists:positions,id_position',
                'data.*.approval_division' => 'required|integer|exists:divisions,id_division',
                'data.*.step_no' => 'required|integer|min:1',
                'data.*.status' => 'required|in:active,inactive',
            ]);

            

            $flowApproval = [
                'request_position' => $request->request_position,
                'request_division' => $request->request_division,
                'status' => 'active',
                'created_by' => Auth::id(),
                'created_at' => now()
            ];
            $insertFlowapproval = DB::table('flowapproval_revisisalesorder')->insertGetId($flowApproval);
            if ($insertFlowapproval) {
                foreach ($request->data as $data) {
                    

                    $dataDetailflowapproval = [
                        'id_flowapproval_revisisalesorder' => $insertFlowapproval,
                        'approval_position' => $data['approval_position'],
                        'approval_division' => $data['approval_division'],
                        'step_no' => $data['step_no'],
                        'status' => $data['status'],
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ];
                    $insertDetailflowapproval =  DB::table('detailflowapproval_revisisalesorder')->insert($dataDetailflowapproval);
                    if (!$insertDetailflowapproval) {
                        throw new Exception('Failed to insert detail flow approval revisi sales order');
                    }
                }
            } else {
                throw new Exception('Failed to insert flow approval revisi sales order');
            }


            DB::commit();
            return ResponseHelper::success('Flow approval for revisi sales order created successfully', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::error($e);
        }
    }

    public function getFlowApprovalRevisiSalesOrder(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');

        $select = [
            'a.id_flowapproval_revisisalesorder',
            'a.request_position',
            'c.name AS request_position_name',
            'a.request_division',
            'd.name AS request_division_name',

            'a.status',
            'a.created_at',
            'a.created_by',
            'b.name AS created_by_name'
        ];

        $flowApprovals = DB::table('flowapproval_revisisalesorder AS a')
            ->select($select)
            ->join('users AS b', 'a.created_by', '=', 'b.id_user')
            ->join('positions AS c', 'a.request_position', '=', 'c.id_position')
            ->join('divisions AS d', 'a.request_division', '=', 'd.id_division')
            ->where(function ($q) use ($search) {
                $q->where('c.name', 'like', '%' . $search . '%')
                    ->orWhere('d.name', 'like', '%' . $search . '%');
            })
            ->paginate($limit);

        $flowApprovals->transform(function ($item) {
            $selectDetailflowapproval = [
                'a.id_detailflowapproval_revisisalesorder',
                'a.id_flowapproval_revisisalesorder',
                'a.approval_position',
                'b.name AS approval_position_name',
                'a.approval_division',
                'c.name AS approval_division_name',
                'a.step_no',
                'a.status',
                'a.created_at',
                'd.name AS created_by_name',
                'a.created_by'
            ];
            $detailFlowapproval = DB::table('detailflowapproval_revisisalesorder AS a')
                ->select($selectDetailflowapproval)
                ->join('positions AS b', 'a.approval_position', '=', 'b.id_position')
                ->join('divisions AS c', 'a.approval_division', '=', 'c.id_division')
                ->join('users AS d', 'a.created_by', '=', 'd.id_user')
                ->where('id_flowapproval_revisisalesorder', $item->id_flowapproval_revisisalesorder)
                ->get();
            $item->detail_flowapproval = $detailFlowapproval;
            return $item;
        });

        return ResponseHelper::success('Flow approvals for revisi sales order retrieved successfully.', $flowApprovals, 200);
    }

    public function updateFlowApprovalRevisiSalesOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_revisisalesorder');
            $flowapproval_revisisalesorder = DB::table('flowapproval_revisisalesorder')->where('id_flowapproval_revisisalesorder', $id)->first();
            $request->validate([
                'id_flowapproval_revisisalesorder' => 'required|integer|exists:flowapproval_revisisalesorder,id_flowapproval_revisisalesorder',
                'request_position' => 'required|integer|exists:positions,id_position',
                'request_division' => 'required|integer|exists:divisions,id_division',
                'status' => 'required|in:active,inactive',
                'detail_flowapproval' => 'required|array',
                'detail_flowapproval.*.id_detailflowapproval_revisisalesorder' => 'nullable|integer|exists:detailflowapproval_revisisalesorder,id_detailflowapproval_revisisalesorder',
                'detail_flowapproval.*.approval_position' => 'required|integer|exists:positions,id_position',
                'detail_flowapproval.*.approval_division' => 'required|integer|exists:divisions,id_division',
                'detail_flowapproval.*.step_no' => 'required|integer|min:1|distinct',
                'detail_flowapproval.*.status' => 'required|in:active,inactive',
            ]);

            $changesFlowapproval = [];
            $changesDetailFlowapproval = [];

            $checkRequestPosition = DB::table('positions')
                ->where('id_position', $request->request_position)
                ->exists();
                if (!$checkRequestPosition) {
                    throw new Exception('Invalid request position');
                }

            $checkRequestDivision = DB::table('divisions')
                ->where('id_division', $request->request_division)
                ->exists();
                if (!$checkRequestDivision) {
                    throw new Exception('Invalid request division');
                }

            $updateFlowapproval = DB::table('flowapproval_revisisalesorder')
                ->where('id_flowapproval_revisisalesorder', $id)
                ->update([
                    'request_position' => $request->input('request_position'),
                    'request_division' => $request->input('request_division'),
                    'status' => $request->input('status'),
                    'updated_at' => now(),
                ]);
            


            if ($request->has('detail_flowapproval')) {
                foreach ($request->input('detail_flowapproval') as $detail) {
                    if (isset($detail['id_detailflowapproval_revisisalesorder']) || $detail['id_detailflowapproval_revisisalesorder'] != NULL) {
                        // Update existing detail
                        $insertDetailflowapproval =  DB::table('detailflowapproval_revisisalesorder')
                            ->where('id_detailflowapproval_revisisalesorder', $detail['id_detailflowapproval_revisisalesorder'])
                            ->update([
                                'approval_position' => $detail['approval_position'],
                                'approval_division' => $detail['approval_division'],
                                'step_no' => $detail['step_no'],
                                'status' => $detail['status'],
                                'updated_at' => now(),
                            ]);
                        if ($insertDetailflowapproval) {
                            // Log the update action
                            DB::table('log_flowapproval_revisisalesorder')->insert([
                                'id_flowapproval_revisisalesorder' => $id,
                                'action' => json_encode([
                                    'id_detailflowapproval_revisisalesorder' => $detail['id_detailflowapproval_revisisalesorder'],
                                    'type' => 'updated',
                                    'data' => $detail
                                ]),
                                'created_by' => Auth::id(),
                                'created_at' => now(),
                            ]);
                        }
                    } else {
                        // Insert new detail
                        $insert= DB::table('detailflowapproval_revisisalesorder')->insert([
                            'id_flowapproval_revisisalesorder' => $id,
                            'approval_position' => $detail['approval_position'],
                            'approval_division' => $detail['approval_division'],
                            'step_no' => $detail['step_no'],
                            'status' => $detail['status'],
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ]);
                        $log = [
                            'id_detailflowapproval_revisisalesorder' => DB::getPdo()->lastInsertId(),
                            'type' => 'insert',
                            'old' => null,
                            'new' => $insert
                        ];
                        DB::table('log_flowapproval_revisisalesorder')->insert([
                            'id_flowapproval_revisisalesorder' => $id,
                            'action' => json_encode($log),
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            DB::commit();
            return ResponseHelper::success('Flow approval for revisi sales order updated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteFlowApprovalRevisiSalesOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_revisisalesorder');
            $request->validate([
                'id_flowapproval_revisisalesorder' => 'required|integer|exists:flowapproval_revisisalesorder,id_flowapproval_revisisalesorder',
            ]);

            DB::table('flowapproval_revisisalesorder')
                ->where('id_flowapproval_revisisalesorder', $id)
                ->update([
                    'status' => 'inactive',
                    'updated_at' => now(),
                ]);

            $changes = [
                'type' => 'deactivated',
            ];
            DB::table('log_flowapproval_revisisalesorder')->insert([
                'id_flowapproval_revisisalesorder' => $id,
                'action' => json_encode($changes),
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            DB::commit();
            return ResponseHelper::success('Flow approval for revisi sales order deleted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function activateFlowApprovalRevisiSalesOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_revisisalesorder');
            $request->validate([
                'id_flowapproval_revisisalesorder' => 'required|integer|exists:flowapproval_revisisalesorder,id_flowapproval_revisisalesorder',
            ]);

            DB::table('flowapproval_revisisalesorder')
                ->where('id_flowapproval_revisisalesorder', $id)
                ->update([
                    'status' => 'active',
                    'updated_at' => now(),
                ]);

            $changes = [
                'type' => 'activated',
            ];

            DB::table('log_flowapproval_revisisalesorder')->insert([
                'id_flowapproval_revisisalesorder' => $id,
                'action' => json_encode($changes),
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            DB::commit();
            return ResponseHelper::success('Flow approval for revisi sales order activated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    //revisi jobsheet
    public function createFlowApprovalRevisiJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request data
            $request->validate([
                'request_position' => 'required|integer|exists:positions,id_position',
                'request_division' => 'required|integer|exists:divisions,id_division',
                'data' => 'nullable|array',
                'data.*.approval_position' => 'required|integer|exists:positions,id_position',
                'data.*.approval_division' => 'required|integer|exists:divisions,id_division',
                'data.*.step_no' => 'required|integer|min:1',
                'data.*.status' => 'required|in:active,inactive',
            ]);

            

            $flowApproval = [
                'request_position' => $request->request_position,
                'request_division' => $request->request_division,
                'status' => 'active',
                'created_by' => Auth::id(),
                'created_at' => now()
            ];
            $insertFlowapproval = DB::table('flowapproval_revisijobsheet')->insertGetId($flowApproval);
            if ($insertFlowapproval) {
                foreach ($request->data as $data) {
                    

                    $dataDetailflowapproval = [
                        'id_flowapproval_revisijobsheet' => $insertFlowapproval,
                        'approval_position' => $data['approval_position'],
                        'approval_division' => $data['approval_division'],
                        'step_no' => $data['step_no'],
                        'status' => $data['status'],
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ];
                    $insertDetailflowapproval =  DB::table('detailflowapproval_revisijobsheet')->insert($dataDetailflowapproval);
                    if (!$insertDetailflowapproval) {
                        throw new Exception('Failed to insert detail flow approval revisi jobsheet');
                    }
                }
            } else {
                throw new Exception('Failed to insert flow approval revisi jobsheet');
            }


            DB::commit();
            return ResponseHelper::success('Flow approval for revisi jobsheet created successfully', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::error($e);
        }
    }

    public function getFlowApprovalRevisiJobsheet(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');

        $select = [
            'a.id_flowapproval_revisijobsheet',
            'a.request_position',
            'c.name AS request_position_name',
            'a.request_division',
            'd.name AS request_division_name',

            'a.status',
            'a.created_at',
            'a.created_by',
            'b.name AS created_by_name'
        ];

        $flowApprovals = DB::table('flowapproval_revisijobsheet AS a')
            ->select($select)
            ->join('users AS b', 'a.created_by', '=', 'b.id_user')
            ->join('positions AS c', 'a.request_position', '=', 'c.id_position')
            ->join('divisions AS d', 'a.request_division', '=', 'd.id_division')
            ->where(function ($q) use ($search) {
                $q->where('c.name', 'like', '%' . $search . '%')
                    ->orWhere('d.name', 'like', '%' . $search . '%');
            })
            ->paginate($limit);

        $flowApprovals->transform(function ($item) {
            $selectDetailflowapproval = [
                'a.id_detailflowapproval_revisijobsheet',
                'a.id_flowapproval_revisijobsheet',
                'a.approval_position',
                'b.name AS approval_position_name',
                'a.approval_division',
                'c.name AS approval_division_name',
                'a.step_no',
                'a.status',
                'a.created_at',
                'd.name AS created_by_name',
                'a.created_by'
            ];
            $detailFlowapproval = DB::table('detailflowapproval_revisijobsheet AS a')
                ->select($selectDetailflowapproval)
                ->join('positions AS b', 'a.approval_position', '=', 'b.id_position')
                ->join('divisions AS c', 'a.approval_division', '=', 'c.id_division')
                ->join('users AS d', 'a.created_by', '=', 'd.id_user')
                ->where('id_flowapproval_revisijobsheet', $item->id_flowapproval_revisijobsheet)
                ->get();
            $item->detail_flowapproval = $detailFlowapproval;
            return $item;
        });

        return ResponseHelper::success('Flow approvals for revisi jobsheet retrieved successfully.', $flowApprovals, 200);
    }

    public function updateFlowApprovalRevisiJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_revisijobsheet');
            $flowapproval_revisijobsheet = DB::table('flowapproval_revisijobsheet')->where('id_flowapproval_revisijobsheet', $id)->first();
            $request->validate([
                'id_flowapproval_revisijobsheet' => 'required|integer|exists:flowapproval_revisijobsheet,id_flowapproval_revisijobsheet',
                'request_position' => 'required|integer|exists:positions,id_position',
                'request_division' => 'required|integer|exists:divisions,id_division',
                'status' => 'required|in:active,inactive',
                'detail_flowapproval' => 'required|array',
                'detail_flowapproval.*.id_detailflowapproval_revisijobsheet' => 'nullable|integer|exists:detailflowapproval_revisijobsheet,id_detailflowapproval_revisijobsheet',
                'detail_flowapproval.*.approval_position' => 'required|integer|exists:positions,id_position',
                'detail_flowapproval.*.approval_division' => 'required|integer|exists:divisions,id_division',
                'detail_flowapproval.*.step_no' => 'required|integer|min:1|distinct',
                'detail_flowapproval.*.status' => 'required|in:active,inactive',
            ]);

            $changesFlowapproval = [];
            $changesDetailFlowapproval = [];

            $checkRequestPosition = DB::table('positions')
                ->where('id_position', $request->request_position)
                ->exists();
                if (!$checkRequestPosition) {
                    throw new Exception('Invalid request position');
                }

            $checkRequestDivision = DB::table('divisions')
                ->where('id_division', $request->request_division)
                ->exists();
                if (!$checkRequestDivision) {
                    throw new Exception('Invalid request division');
                }

            $updateFlowapproval = DB::table('flowapproval_revisijobsheet')
                ->where('id_flowapproval_revisijobsheet', $id)
                ->update([
                    'request_position' => $request->input('request_position'),
                    'request_division' => $request->input('request_division'),
                    'status' => $request->input('status'),
                    'updated_at' => now(),
                ]);
            


            if ($request->has('detail_flowapproval')) {
                foreach ($request->input('detail_flowapproval') as $detail) {
                    if (isset($detail['id_detailflowapproval_revisijobsheet']) || $detail['id_detailflowapproval_revisijobsheet'] != NULL) {
                        // Update existing detail
                        $insertDetailflowapproval =  DB::table('detailflowapproval_revisijobsheet')
                            ->where('id_detailflowapproval_revisijobsheet', $detail['id_detailflowapproval_revisijobsheet'])
                            ->update([
                                'approval_position' => $detail['approval_position'],
                                'approval_division' => $detail['approval_division'],
                                'step_no' => $detail['step_no'],
                                'status' => $detail['status'],
                                'updated_at' => now(),
                            ]);
                        if ($insertDetailflowapproval) {
                            // Log the update action
                            DB::table('log_flowapproval_revisijobsheet')->insert([
                                'id_flowapproval_revisijobsheet' => $id,
                                'action' => json_encode([
                                    'id_detailflowapproval_revisijobsheet' => $detail['id_detailflowapproval_revisijobsheet'],
                                    'type' => 'updated',
                                    'data' => $detail
                                ]),
                                'created_by' => Auth::id(),
                                'created_at' => now(),
                            ]);
                        }
                    } else {
                        // Insert new detail
                        $insert= DB::table('detailflowapproval_revisijobsheet')->insert([
                            'id_flowapproval_revisijobsheet' => $id,
                            'approval_position' => $detail['approval_position'],
                            'approval_division' => $detail['approval_division'],
                            'step_no' => $detail['step_no'],
                            'status' => $detail['status'],
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ]);
                        $log = [
                            'id_detailflowapproval_revisijobsheet' => DB::getPdo()->lastInsertId(),
                            'type' => 'insert',
                            'old' => null,
                            'new' => $insert
                        ];
                        DB::table('log_flowapproval_revisijobsheet')->insert([
                            'id_flowapproval_revisijobsheet' => $id,
                            'action' => json_encode($log),
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            DB::commit();
            return ResponseHelper::success('Flow approval for revisi jobsheet updated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteFlowApprovalRevisiJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_revisijobsheet');
            $request->validate([
                'id_flowapproval_revisijobsheet' => 'required|integer|exists:flowapproval_revisijobsheet,id_flowapproval_revisijobsheet',
            ]);

            DB::table('flowapproval_revisijobsheet')
                ->where('id_flowapproval_revisijobsheet', $id)
                ->update([
                    'status' => 'inactive',
                    'updated_at' => now(),
                ]);

            $changes = [
                'type' => 'deactivated',
            ];
            DB::table('log_flowapproval_revisijobsheet')->insert([
                'id_flowapproval_revisijobsheet' => $id,
                'action' => json_encode($changes),
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            DB::commit();
            return ResponseHelper::success('Flow approval for revisi jobsheet deleted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function activateFlowApprovalRevisiJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_flowapproval_revisijobsheet');
            $request->validate([
                'id_flowapproval_revisijobsheet' => 'required|integer|exists:flowapproval_revisijobsheet,id_flowapproval_revisijobsheet',
            ]);

            DB::table('flowapproval_revisijobsheet')
                ->where('id_flowapproval_revisijobsheet', $id)
                ->update([
                    'status' => 'active',
                    'updated_at' => now(),
                ]);

            $changes = [
                'type' => 'activated',
            ];

            DB::table('log_flowapproval_revisijobsheet')->insert([
                'id_flowapproval_revisijobsheet' => $id,
                'action' => json_encode($changes),
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            DB::commit();
            return ResponseHelper::success('Flow approval for revisi jobsheet activated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }



}
