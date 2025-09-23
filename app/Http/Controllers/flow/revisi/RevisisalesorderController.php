<?php

namespace App\Http\Controllers\flow\revisi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class RevisisalesorderController extends Controller
{
    public function createRevisiSalesOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_salesorder' => 'required|integer|exists:salesorder,id_salesorder',
                'revision_notes' => 'required|string|max:255',
                'data_selling' => 'required|array',
                'data_selling.*.id_typeselling' => 'required|integer|exists:typeselling,id_typeselling',
                'data_selling.*.selling_value' => 'required|numeric|min:0',
                'data_selling.*.charge_by' => 'required|in:chargeable_weight,gross_weight,awb',
                'data_selling.*.description' => 'nullable|string|max:255',
            ]);

            $salesorder = DB::table('salesorder')->where('id_salesorder', $request->input('id_salesorder'))->first();
            $approvalSalesorder = DB::table('approval_salesorder')
                ->where('id_salesorder', $request->input('id_salesorder'))
                ->where('status', 'pending')
                ->first();
            if ($salesorder->status_approval != 'so_approved') {
                throw new Exception('Sales Order must be in approved status and have a pending approval to create a revision');
            }




            $revisiSalesorder = DB::table('revisisalesorder')->insertGetId([
                'id_salesorder' => $request->input('id_salesorder'),
                'revision_notes' => $request->input('revision_notes'),
                'created_at' => now(),
                'created_by' => Auth::id(),
                'status_revisisalesorder' => 'revision_created',
            ]);



            if ($revisiSalesorder) {

                $sellingfrom = DB::table('selling_salesorder')
                    ->where('id_salesorder', $request->input('id_salesorder'))
                    ->get();

                if ($sellingfrom->isEmpty()) {
                    throw new Exception('No selling data found for the given sales order');
                } else {
                    foreach ($sellingfrom as $item) {
                        $insertDetailFrom = DB::table('detailfrom_revisisalesorder')->insert([
                            'id_revisisalesorder' => $revisiSalesorder, // Will be set after creating revisisalesorder
                            'id_typeselling' => $item->id_typeselling,
                            'selling_value' => $item->selling_value,
                            'charge_by' => $item->charge_by,
                            'description' => $item->description,
                        ]);

                        if (!$insertDetailFrom) {
                            throw new Exception('Failed to insert detail from selling sales order');
                        }
                    }
                }

                $dataSelling = $request->input('data_selling');
                foreach ($dataSelling as $item) {
                    DB::table('detailto_revisisalesorder')->insert([
                        'id_revisisalesorder' => $revisiSalesorder,
                        'id_typeselling' => $item['id_typeselling'],
                        'selling_value' => $item['selling_value'],
                        'charge_by' => $item['charge_by'],
                        'description' => $item['description'] ?? null,
                    ]);
                }

                $id_position = Auth::user()->id_position;
                $id_division = Auth::user()->id_division;
                if (!$id_position || !$id_division) {
                    throw new Exception('Invalid user position or division');
                }
                $flow_approval = DB::table('flowapproval_revisisalesorder')
                    ->where(['request_position' => $id_position, 'request_division' => $id_division])
                    ->first();
                if (!$flow_approval) {
                    throw new Exception('No flow approval found for the user position and division');
                } else {

                    $detail_flowapproval = DB::table('detailflowapproval_revisisalesorder')
                        ->where('id_flowapproval_revisisalesorder', $flow_approval->id_flowapproval_revisisalesorder)
                        ->get();
                    foreach ($detail_flowapproval as $approval) {
                        $approval = [
                            'id_revisisalesorder' => $revisiSalesorder,
                            'approval_position' => $approval->approval_position,
                            'approval_division' => $approval->approval_division,
                            'step_no' => $approval->step_no,
                            'status' => 'pending',
                            'created_by' => Auth::id(),
                        ];
                        DB::table('approval_revisisalesorder')->insert($approval);
                    }
                }
                $insertDetail =  DB::table('log_revisisalesorder')->insert([
                    'id_revisisalesorder' => $revisiSalesorder,
                    'action' => json_encode(['action' => 'created', 'notes' => $request->input('revision_notes')]),
                    'created_at' => now(),
                    'created_by' => Auth::id(),
                ]);

                if (!$insertDetail) {
                    throw new Exception('Failed to create log for Revisi Sales Order');
                }
                DB::commit();

                return ResponseHelper::success('Revisi Sales Order created successfully', NULL, 201);
            } else {
                throw new Exception('Failed to create Revisi Sales Order');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function getRevisiSalesOrder(Request $request)
    {
        $limit = $request->input('limit', 10);
        $searchKey = $request->input('searchKey', '');

        $select = [
            'r.id_revisisalesorder',
            'r.id_salesorder',
            's.no_salesorder',
            'r.revision_notes',
            'r.status_revisisalesorder',
            'r.created_at',
            'r.created_by',
            'u.name AS created_by_name',
            'r.updated_at',
            'r.deleted_at',
            'r.deleted_by',
            'ud.name AS deleted_by_name'

           
        ];

        $revisiSalesOrders = DB::table('revisisalesorder AS r')
            ->select($select)
            ->join('salesorder AS s', 'r.id_salesorder', '=', 's.id_salesorder')
            ->join('users AS u', 'r.created_by', '=', 'u.id_user')
            ->join('users AS ud', 'r.deleted_by', '=', 'ud.id_user', 'left')
           
            ->where(function ($query) use ($searchKey) {
                $query->where('s.no_salesorder', 'LIKE', "%{$searchKey}%")
                    ->orWhere('r.revision_notes', 'LIKE', "%{$searchKey}%")
                    ->orWhere('u.name', 'LIKE', "%{$searchKey}%");
                  
                  
            })
            ->orderBy('r.created_at', 'desc')
            ->paginate($limit);

        return ResponseHelper::success('Revisi Sales Orders retrieved successfully', $revisiSalesOrders);
    }

    public function getRevisiSalesOrderById(Request $request)
    {
        try {
            $id = $request->input('id_revisisalesorder');
            $select = [
                'r.id_revisisalesorder',
                'r.id_salesorder',
                's.no_salesorder',
                'r.revision_notes',
                'r.status_revisisalesorder',
                'r.created_at',
                'r.created_by',
                'u.name AS created_by_name',
                'r.updated_at',
                
            ];

            $revisiSalesOrder = DB::table('revisisalesorder AS r')
                ->select($select)
                ->join('salesorder AS s', 'r.id_salesorder', '=', 's.id_salesorder')
                ->join('users AS u', 'r.created_by', '=', 'u.id_user')
               
                ->where('r.id_revisisalesorder', $id)
                ->first();

            if (!$revisiSalesOrder) {
                throw new Exception('Revisi Sales Order not found');
            }

            $detailsFrom = DB::table('detailfrom_revisisalesorder AS dfr')
                ->select(
                    'dfr.id_detailfrom_revisisalesorder',
                    'dfr.id_typeselling',
                    'ts.name AS typeselling_name',
                    'dfr.selling_value',
                    'dfr.charge_by',
                    'dfr.description'
                )
                ->join('typeselling AS ts', 'dfr.id_typeselling', '=', 'ts.id_typeselling')
                ->where('dfr.id_revisisalesorder', $id)
                ->get();

            $detailsTo = DB::table('detailto_revisisalesorder AS dtr')
                ->select(
                    'dtr.id_detailto_revisisalesorder',
                    'dtr.id_typeselling',
                    'ts.name AS typeselling_name',
                    'dtr.selling_value',
                    'dtr.charge_by',
                    'dtr.description'
                )
                ->join('typeselling AS ts', 'dtr.id_typeselling', '=', 'ts.id_typeselling')
                ->where('dtr.id_revisisalesorder', $id)
                ->get();

            $logs = DB::table('log_revisisalesorder AS lr')
                ->select(
                    'lr.id_log_revisisalesorder',
                    'lr.action',
                    'lr.created_at',
                    'lr.created_by',
                    'u.name AS created_by_name'
                )
                ->join('users AS u', 'lr.created_by', '=', 'u.id_user')
                ->where('lr.id_revisisalesorder', $id)
                ->orderBy('lr.created_at', 'desc')
                ->get();
                $logs->transform(function ($log) {
                    $log->action = json_decode($log->action, true);
                    return $log;
                });

                $approval_revisisalesorder = DB::table('approval_revisisalesorder AS ar')
                ->select(
                    'ar.id_approval_revisisalesorder',
                    'ar.id_revisisalesorder',
                    'ar.approval_position',
                    'p.name AS approval_position_name',
                    'ar.approval_division',
                    'd.name AS approval_division_name',
                    'ar.status',
                    'ar.created_at',
                    'ar.created_by',
                    'u.name AS created_by_name'
                )
                ->join('users AS u', 'ar.created_by', '=', 'u.id_user')
                ->join('positions AS p', 'ar.approval_position', '=', 'p.id_position')
                ->join('divisions AS d', 'ar.approval_division', '=', 'd.id_division')
                ->where('ar.id_revisisalesorder', $id)
                ->orderBy('ar.created_at', 'desc')
                ->get();
                $revisiSalesOrder->approval_revisisalesorder = $approval_revisisalesorder;
            $revisiSalesOrder->details_from = $detailsFrom;
            $revisiSalesOrder->details_to = $detailsTo;
            $revisiSalesOrder->logs = $logs;
            return ResponseHelper::success('Revisi Sales Order retrieved successfully', $revisiSalesOrder, 200);
        } catch (Exception $e) {
            return ResponseHelper::error($e);
        }
    }

    public function updateRevisiSalesOrder(Request $request)
    {
        $request->validate([
            'id_revisisalesorder' => 'required|integer|exists:revisisalesorder,id_revisisalesorder',
            'revision_notes' => 'required|string|max:255',
            'data_selling' => 'required|array',
            'data_selling.*.id_typeselling' => 'required|integer|exists:typeselling,id_typeselling',
            'data_selling.*.selling_value' => 'required|numeric|min:0',
            'data_selling.*.charge_by' => 'required|in:chargeable_weight,gross_weight,awb',
            'data_selling.*.description' => 'nullable|string|max:255',
        ]);
        DB::beginTransaction();
        try {
            $idRevisi = $request->input('id_revisisalesorder');
            $revisiSalesOrder = DB::table('revisisalesorder')->where('id_revisisalesorder', $idRevisi)->first();
            $updateRevisi = DB::table('revisisalesorder')
                ->where('id_revisisalesorder', $idRevisi)
                ->update([
                    'revision_notes' => $request->input('revision_notes'),
                    'updated_at' => now(),
                   
                ]);

            if ($updateRevisi === false) {
                throw new Exception('Failed to update Revisi Sales Order');
            }

            // Delete existing details to and from
            DB::table('detailfrom_revisisalesorder')->where('id_revisisalesorder', $idRevisi)->delete();
            DB::table('detailto_revisisalesorder')->where('id_revisisalesorder', $idRevisi)->delete();

            // Re-insert details from the original sales order
            $sellingfrom = DB::table('selling_salesorder')
                ->where('id_salesorder', $revisiSalesOrder->id_salesorder)
                ->get();

            if ($sellingfrom->isEmpty()) {
                throw new Exception('No selling data found for the given sales order');
            } else {
                foreach ($sellingfrom as $item) {
                    $insertDetailFrom = DB::table('detailfrom_revisisalesorder')->insert([
                        'id_revisisalesorder' => $idRevisi,
                        'id_typeselling' => $item->id_typeselling,
                        'selling_value' => $item->selling_value,
                        'charge_by' => $item->charge_by,
                        'description' => $item->description,
                    ]);

                    if (!$insertDetailFrom) {
                        throw new Exception('Failed to insert detail from selling sales order');
                    }
                }
            }

            // Insert new details to
            $dataSelling = $request->input('data_selling');
            foreach ($dataSelling as $item) {
                DB::table('detailto_revisisalesorder')->insert([
                    'id_revisisalesorder' => $idRevisi,
                    'id_typeselling' => $item['id_typeselling'],
                    'selling_value' => $item['selling_value'],
                    'charge_by' => $item['charge_by'],
                    'description' => $item['description'],
                ]);
            }

            $dataLog = [
                'action' => 'updated',
                'notes_before' => $revisiSalesOrder->revision_notes,
                'notes_after' => $request->input('revision_notes'),

                'data' => [
                    'before' => [
                        'from' => DB::table('detailfrom_revisisalesorder')->where('id_revisisalesorder', $idRevisi)->get(),
                        'to' => DB::table('detailto_revisisalesorder')->where('id_revisisalesorder', $idRevisi)->get(),
                    ],
                    'after' => [
                        'from' => $sellingfrom,
                        'to' => $dataSelling,
                    ]
                ]
            ];

            $log = DB::table('log_revisisalesorder')->insert([
                'id_revisisalesorder' => $idRevisi,
                'action' => json_encode($dataLog),
                'created_at' => now(),
                'created_by' => Auth::id(),
            ]);
            if (!$log) {
                throw new Exception('Failed to create log for Revisi Sales Order');
            }

            DB::commit();
            return ResponseHelper::success('Revisi Sales Order updated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteRevisiSalesOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_revisisalesorder' => 'required|integer|exists:revisisalesorder,id_revisisalesorder',
            ]);

            $idRevisi = $request->input('id_revisisalesorder');
            $revisiSalesOrder = DB::table('revisisalesorder')->where('id_revisisalesorder', $idRevisi)->first();
            if (!$revisiSalesOrder) {
                throw new Exception('Revisi Sales Order not found');
            }

            $deleteRevisi = DB::table('revisisalesorder')
                ->where('id_revisisalesorder', $idRevisi)
                ->update([
                    'deleted_at' => now(),
                    'deleted_by' => Auth::id(),
                    'status_revisisalesorder' => 'revision_deleted',
                ]);

            if ($deleteRevisi === false) {
                throw new Exception('Failed to delete Revisi Sales Order');
            }

            DB::commit();
            return ResponseHelper::success('Revisi Sales Order deleted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function actionRevisiSalesorder(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_approval_revisisalesorder' => 'required|integer|exists:approval_revisisalesorder,id_approval_revisisalesorder',
                'id_revisisalesorder' => 'required|integer|exists:revisisalesorder,id_revisisalesorder',
                'remarks' => 'nullable|string|max:255',
                'status' => 'required|in:approved,rejected'
            ]);

            $approval = DB::table('approval_revisisalesorder')
                ->where('id_approval_revisisalesorder', $request->id_approval_revisisalesorder)
                ->where('id_revisisalesorder', $request->id_revisisalesorder)
                ->first();

            if ($approval) {
                if ($approval->approval_position == Auth::user()->id_position && $approval->approval_division == Auth::user()->id_division) {
                    $update = DB::table('approval_revisisalesorder')
                        ->where('id_approval_revisisalesorder', $request->id_approval_revisisalesorder)
                        ->where('approval_position', Auth::user()->id_position)
                        ->where('approval_division', Auth::user()->id_division)
                        ->update([
                            'status' => $request->status,
                            'remarks' => $request->remarks ?? null,
                            'approved_by' => Auth::id(),
                            'updated_at' => now(),
                        ]);

                    if (!$update) {
                        throw new Exception('Failed to update approval status because');
                    } else {
                        if ($request->status == 'rejected') {
                            // update status sales order to rejected
                            $updateSalesorder = DB::table('revisisalesorder')
                                ->where('id_revisisalesorder', $request->id_revisisalesorder)
                                ->update([
                                    'status_revisisalesorder' => 'revision_rejected',
                                    'updated_at' => now(),
                                ]);
                            if (!$updateSalesorder) {
                                throw new Exception('Failed to update revisi sales order status to rejected');
                            }
                            $log = [
                                'id_revisisalesorder' => $request->id_revisisalesorder,
                                'action' => json_encode([
                                    'type' => 'rejected',
                                    'data' => [
                                        'remarks' => $request->remarks ?? null,
                                        'rejected_by' => Auth::id(),
                                        'rejected_at' => now(),
                                    ]
                                ]),
                                'created_by' => Auth::id(),
                                'created_at' => now(),
                            ];
                        } else {
                            // Cek apakah ada pending approval lagi
                            $pendingApproval = DB::table('approval_revisisalesorder')
                                ->where('id_revisisalesorder', $request->id_revisisalesorder)
                                ->where('status', 'pending')
                                ->orderBy('step_no', 'ASC')
                                ->first();
                            if (!$pendingApproval) {
                                // update status revisi sales order to approved
                                $updateSalesorder = DB::table('revisisalesorder')
                                    ->where('id_revisisalesorder', $request->id_revisisalesorder)
                                    ->update([
                                        'status_revisisalesorder' => 'revision_approved',
                                        'updated_at' => now(),
                                    ]);
                                if (!$updateSalesorder) {
                                    throw new Exception('Failed to update revisi sales order status to approved');
                                }
                            }
                            $log = [
                                'id_revisisalesorder' => $request->id_revisisalesorder,
                                'action' => json_encode([
                                    'type' => 'approved',
                                    'data' => [
                                        'remarks' => $request->remarks ?? null,
                                        'approved_by' => Auth::id(),
                                        'approved_at' => now(),
                                    ]
                                ]),
                                'created_by' => Auth::id(),
                                'created_at' => now(),
                            ];
                        }
                    }
                } else {
                    throw new Exception('You are not authorized to update this approval status');
                }
            } else {
                throw new Exception('Approval record not found');
            }
            $insertLog = DB::table('log_revisisalesorder')->insert($log);
            if (!$insertLog) {
                throw new Exception('Failed to create log for revisi sales order action');
            }
            DB::commit();
            return ResponseHelper::success('Sales order approved successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
