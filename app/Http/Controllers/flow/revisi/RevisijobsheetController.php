<?php

namespace App\Http\Controllers\flow\revisi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class RevisijobsheetController extends Controller
{
    public function createRevisiJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_jobsheet' => 'required|integer|exists:jobsheet,id_jobsheet',
                'revision_notes' => 'required|string|max:255',
                'data_cost' => 'required|array',
                'data_cost.*.id_typecost' => 'required|integer|exists:typecost,id_typecost',
                'data_cost.*.cost_value' => 'required|numeric|min:0',
                'data_cost.*.charge_by' => 'required|in:chargeable_weight,gross_weight,awb',
                'data_cost.*.description' => 'nullable|string|max:255',
            ]);

            $jobsheet = DB::table('jobsheet')->where('id_jobsheet', $request->input('id_jobsheet'))->first();
            $approvalJobsheet = DB::table('approval_jobsheet')
                ->where('id_jobsheet', $request->input('id_jobsheet'))
                ->where('status', 'pending')
                ->first();
            if ($jobsheet->status_approval != 'js_approved') {
                throw new Exception('Jobsheet must be in approved status and have a pending approval to create a revision');
            }




            $revisiJobsheet = DB::table('revisijobsheet')->insertGetId([
                'id_jobsheet' => $request->input('id_jobsheet'),
                'revision_notes' => $request->input('revision_notes'),
                'created_at' => now(),
                'created_by' => Auth::id(),
                'status_revisijobsheet' => 'revision_created',
            ]);



            if ($revisiJobsheet) {

                $costFrom = DB::table('cost_jobsheet')
                    ->where('id_jobsheet', $request->input('id_jobsheet'))
                    ->get();

                if ($costFrom->isEmpty()) {
                    throw new Exception('No cost data found for the given jobsheet');
                } else {
                    foreach ($costFrom as $item) {
                        $insertDetailFrom = DB::table('detailfrom_revisijobsheet')->insert([
                            'id_revisijobsheet' => $revisiJobsheet, // Will be set after creating revisijobsheet
                            'id_typecost' => $item->id_typecost,
                            'cost_value' => $item->cost_value,
                            'charge_by' => $item->charge_by,
                            'description' => $item->description,
                        ]);

                        if (!$insertDetailFrom) {
                            throw new Exception('Failed to insert detail from cost jobsheet');
                        }
                    }
                }

                $dataCost = $request->input('data_cost');
                foreach ($dataCost as $item) {
                    DB::table('detailto_revisijobsheet')->insert([
                        'id_revisijobsheet' => $revisiJobsheet,
                        'id_typecost' => $item['id_typecost'],
                        'cost_value' => $item['cost_value'],
                        'charge_by' => $item['charge_by'],
                        'description' => $item['description'] ?? null,
                    ]);
                }

                $id_position = Auth::user()->id_position;
                $id_division = Auth::user()->id_division;
                if (!$id_position || !$id_division) {
                    throw new Exception('Invalid user position or division');
                }
                $flow_approval = DB::table('flowapproval_jobsheet')
                    ->where(['request_position' => $id_position, 'request_division' => $id_division])
                    ->first();
                if (!$flow_approval) {
                    throw new Exception('No flow approval found for the user position and division');
                } else {

                    $detail_flowapproval = DB::table('detailflowapproval_revisijobsheet')
                        ->where('id_flowapproval_revisijobsheet', $flow_approval->id_flowapproval_revisijobsheet)
                        ->get();
                    foreach ($detail_flowapproval as $approval) {
                        $approval = [
                            'id_revisijobsheet' => $revisiJobsheet,
                            'approval_position' => $approval->approval_position,
                            'approval_division' => $approval->approval_division,
                            'step_no' => $approval->step_no,
                            'status' => 'pending',
                            'created_by' => Auth::id(),
                        ];
                        DB::table('approval_revisijobsheet')->insert($approval);
                    }
                }
                $insertDetail =  DB::table('log_revisijobsheet')->insert([
                    'id_revisijobsheet' => $revisiJobsheet,
                    'action' => json_encode(['action' => 'created', 'notes' => $request->input('revision_notes')]),
                    'created_at' => now(),
                    'created_by' => Auth::id(),
                ]);

                if (!$insertDetail) {
                    throw new Exception('Failed to create log for Revisi Jobsheet');
                }
                DB::commit();

                return ResponseHelper::success('Revisi Jobsheet created successfully', NULL, 201);
            } else {
                throw new Exception('Failed to create Revisi Jobsheet');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function getRevisiJobsheet(Request $request)
    {
        $limit = $request->input('limit', 10);
        $searchKey = $request->input('searchKey', '');

        $select = [
            'r.id_revisijobsheet',
            'r.id_jobsheet',
            's.no_jobsheet',
            'r.revision_notes',
            'r.status_revisijobsheet',
            'r.created_at',
            'r.created_by',
            'u.name AS created_by_name',
            'r.updated_at',
            'r.deleted_at',
            'ud.name AS deleted_by_name',
            

        ];

        $revisiJobsheets = DB::table('revisijobsheet AS r')
            ->select($select)
            ->join('jobsheet AS s', 'r.id_jobsheet', '=', 's.id_jobsheet')
            ->join('users AS u', 'r.created_by', '=', 'u.id_user')
            ->leftJoin('users AS ud', 'r.deleted_by', '=', 'ud.id_user')
            ->where(function ($query) use ($searchKey) {
                $query->where('s.no_jobsheet', 'LIKE', "%{$searchKey}%")
                    ->orWhere('r.revision_notes', 'LIKE', "%{$searchKey}%")
                    ->orWhere('u.name', 'LIKE', "%{$searchKey}%");
                   
            })
            ->orderBy('r.created_at', 'desc')
            ->paginate($limit);

        return ResponseHelper::success('Revisi jobsheets retrieved successfully', $revisiJobsheets);
    }

    public function getRevisiJobsheetById(Request $request)
    {
        try {
            $id = $request->input('id_revisijobsheet');
            $select = [
                'r.id_revisijobsheet',
                'r.id_jobsheet',
                's.no_jobsheet',
                'r.revision_notes',
                'r.status_revisijobsheet',
                'r.created_at',
                'r.created_by',
                'u.name AS created_by_name',
                'r.updated_at',
               
            ];

            $revisiJobsheet = DB::table('revisijobsheet AS r')
                ->select($select)
                ->join('jobsheet AS s', 'r.id_jobsheet', '=', 's.id_jobsheet')
                ->join('users AS u', 'r.created_by', '=', 'u.id_user')
               
                ->where('r.id_revisijobsheet', $id)
                ->first();

            if (!$revisiJobsheet) {
                throw new Exception('Revisi jobsheet not found');
            }

            $detailsFrom = DB::table('detailfrom_revisijobsheet AS dfr')
                ->select(
                    'dfr.id_detailfrom_revisijobsheet',
                    'dfr.id_typecost',
                    'ts.name AS typecost_name',
                    'ts.initials AS typecost_initial',
                    'dfr.cost_value',
                    'dfr.charge_by',
                    'dfr.description'
                )
                ->join('typecost AS ts', 'dfr.id_typecost', '=', 'ts.id_typecost')
                ->where('dfr.id_revisijobsheet', $id)
                ->get();

            $detailsTo = DB::table('detailto_revisijobsheet AS dtr')
                ->select(
                    'dtr.id_detailto_revisijobsheet',
                    'dtr.id_typecost',
                    'ts.name AS typecost_name',
                    'ts.initials AS typecost_initial',
                    'dtr.cost_value',
                    'dtr.charge_by',
                    'dtr.description'
                )
                ->join('typecost AS ts', 'dtr.id_typecost', '=', 'ts.id_typecost')
                ->where('dtr.id_revisijobsheet', $id)
                ->get();

            $logs = DB::table('log_revisijobsheet AS lr')
                ->select(
                    'lr.id_log_revisijobsheet',
                    'lr.action',
                    'lr.created_at',
                    'lr.created_by',
                    'u.name AS created_by_name'
                )
                ->join('users AS u', 'lr.created_by', '=', 'u.id_user')
                ->where('lr.id_revisijobsheet', $id)
                ->orderBy('lr.created_at', 'desc')
                ->get();
            $revisiJobsheet->details_from = $detailsFrom;
            $revisiJobsheet->details_to = $detailsTo;
            $revisiJobsheet->logs = $logs;
            return ResponseHelper::success('Revisi jobsheet retrieved successfully', $revisiJobsheet, 200);
        } catch (Exception $e) {
            return ResponseHelper::error($e);
        }
    }

    public function updateRevisiJobsheet(Request $request)
    {
        $request->validate([
            'id_revisijobsheet' => 'required|integer|exists:revisijobsheet,id_revisijobsheet',
            'revision_notes' => 'required|string|max:255',
            'data_cost' => 'required|array',
            'data_cost.*.id_typecost' => 'required|integer|exists:typecost,id_typecost',
            'data_cost.*.cost_value' => 'required|numeric|min:0',
            'data_cost.*.charge_by' => 'required|in:chargeable_weight,gross_weight,awb',
            'data_cost.*.description' => 'nullable|string|max:255',
        ]);
        DB::beginTransaction();
        try {
            $idRevisi = $request->input('id_revisijobsheet');
            $revisiJobsheet = DB::table('revisijobsheet')->where('id_revisijobsheet', $idRevisi)->first();
            $updateRevisi = DB::table('revisijobsheet')
                ->where('id_revisijobsheet', $idRevisi)
                ->update([
                    'revision_notes' => $request->input('revision_notes'),
                    'status_revisijobsheet' => 'revision_updated',
                    'updated_at' => now(),
                    
                ]);

            if ($updateRevisi === false) {
                throw new Exception('Failed to update Revisi jobsheet');
            }

            // Delete existing details to and from
            DB::table('detailfrom_revisijobsheet')->where('id_revisijobsheet', $idRevisi)->delete();
            DB::table('detailto_revisijobsheet')->where('id_revisijobsheet', $idRevisi)->delete();

            // Re-insert details from the original jobsheet
            $costfrom = DB::table('cost_jobsheet')
                ->where('id_jobsheet', $revisiJobsheet->id_jobsheet)
                ->get();

            if ($costfrom->isEmpty()) {
                throw new Exception('No cost data found for the given jobsheet');
            } else {
                foreach ($costfrom as $item) {
                    $insertDetailFrom = DB::table('detailfrom_revisijobsheet')->insert([
                        'id_revisijobsheet' => $idRevisi,
                        'id_typecost' => $item->id_typecost,
                        'cost_value' => $item->cost_value,
                        'charge_by' => $item->charge_by,
                        'description' => $item->description,
                    ]);

                    if (!$insertDetailFrom) {
                        throw new Exception('Failed to insert detail from cost jobsheet');
                    }
                }
            }

            // Insert new details to
            $dataCost = $request->input('data_cost');
            foreach ($dataCost as $item) {
                DB::table('detailto_revisijobsheet')->insert([
                    'id_revisijobsheet' => $idRevisi,
                    'id_typecost' => $item['id_typecost'],
                    'cost_value' => $item['cost_value'],
                    'charge_by' => $item['charge_by'],
                    'description' => $item['description'],
                ]);
            }

            $dataLog = [
                'action' => 'updated',
                'notes_before' => $revisiJobsheet->revision_notes,
                'notes_after' => $request->input('revision_notes'),

                'data' => [
                    'before' => [
                        'from' => DB::table('detailfrom_revisijobsheet')->where('id_revisijobsheet', $idRevisi)->get(),
                        'to' => DB::table('detailto_revisijobsheet')->where('id_revisijobsheet', $idRevisi)->get(),
                    ],
                    'after' => [
                        'from' => $costfrom,
                        'to' => $dataCost,
                    ]
                ]
            ];

            $log = DB::table('log_revisijobsheet')->insert([
                'id_revisijobsheet' => $idRevisi,
                'action' => json_encode($dataLog),
                'created_at' => now(),
                'created_by' => Auth::id(),
            ]);
            if (!$log) {
                throw new Exception('Failed to create log for Revisi jobsheet');
            }

            DB::commit();
            return ResponseHelper::success('Revisi jobsheet updated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteRevisiJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_revisijobsheet' => 'required|integer|exists:revisijobsheet,id_revisijobsheet',
            ]);

            $idRevisi = $request->input('id_revisijobsheet');
            $revisiJobsheet = DB::table('revisijobsheet')->where('id_revisijobsheet', $idRevisi)->first();
            if (!$revisiJobsheet) {
                throw new Exception('Revisi jobsheet not found');
            }

            $deleteRevisi = DB::table('revisijobsheet')
                ->where('id_revisijobsheet', $idRevisi)
                ->update([
                    'deleted_at' => now(),
                    'deleted_by' => Auth::id(),
                    'status_revisijobsheet' => 'revision_deleted',
                ]);

            if ($deleteRevisi === false) {
                throw new Exception('Failed to delete Revisi jobsheet');
            }

            DB::commit();
            return ResponseHelper::success('Revisi jobsheet deleted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function actionRevisiJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_approval_revisijobsheet' => 'required|integer|exists:approval_revisijobsheet,id_approval_revisijobsheet',
                'id_revisijobsheet' => 'required|integer|exists:revisijobsheet,id_revisijobsheet',
                'remarks' => 'nullable|string|max:255',
                'status' => 'required|in:approved,rejected'
            ]);

            $approval = DB::table('approval_revisijobsheet')
                ->where('id_approval_revisijobsheet', $request->id_approval_revisijobsheet)
                ->where('id_revisijobsheet', $request->id_revisijobsheet)
                ->first();

            if ($approval) {
                if ($approval->approval_position == Auth::user()->id_position && $approval->approval_division == Auth::user()->id_division) {
                    $update = DB::table('approval_revisijobsheet')
                        ->where('id_approval_revisijobsheet', $request->id_approval_revisijobsheet)
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
                            // update status jobsheet to rejected
                            $updateJobsheet = DB::table('revisijobsheet')
                                ->where('id_revisijobsheet', $request->id_revisijobsheet)
                                ->update([
                                    'status_revisijobsheet' => 'revision_rejected',
                                    'updated_at' => now(),
                                ]);
                            if (!$updateJobsheet) {
                                throw new Exception('Failed to update revisi jobsheet status to rejected');
                            }
                            $log = [
                                'id_revisijobsheet' => $request->id_revisijobsheet,
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
                            $pendingApproval = DB::table('approval_revisijobsheet')
                                ->where('id_revisijobsheet', $request->id_revisijobsheet)
                                ->where('status_revisijobsheet', 'pending')
                                ->orderBy('step_no', 'ASC')
                                ->first();
                            if (!$pendingApproval) {
                                // update status revisi jobsheet to approved
                                $updateJobsheet = DB::table('revisijobsheet')
                                    ->where('id_revisijobsheet', $request->id_revisijobsheet)
                                    ->update([
                                        'status_revisijobsheet' => 'revision_approved',
                                        'updated_at' => now(),
                                    ]);
                                if (!$updateJobsheet) {
                                    throw new Exception('Failed to update revisi jobsheet status to approved');
                                }
                            }
                            $log = [
                                'id_revisijobsheet' => $request->id_revisijobsheet,
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
            $insertLog = DB::table('log_revisijobsheet')->insert($log);
            if (!$insertLog) {
                throw new Exception('Failed to create log for revisi jobsheet action');
            }
            DB::commit();
            return ResponseHelper::success('jobsheet approved successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
