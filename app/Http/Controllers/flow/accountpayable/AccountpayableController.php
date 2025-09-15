<?php

namespace App\Http\Controllers\flow\accountpayable;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\NumberHelper;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Support\Facades\Storage;

class AccountpayableController extends Controller
{
    public function createAccountpayable(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'type' => 'required|in:RE,PO,CA,CAR',
                'description' => 'required|string|max:255',
                'no_ca' => 'nullable|string|max:100|exists:account_payable,no_accountpayable,type,CA|unique:account_payable,no_ca',
                'detail' => 'required|array',
                'detail.*.type_pengeluaran' => 'required|numeric|exists:type_pengeluaran,id_typepengeluaran',
                'detail.*.description' => 'required|string|max:255',
                'detail.*.amount' => 'required|numeric|min:0',
                'detail.*.attachment' => 'required|string',
            ], [
                'no_ca.exists' => 'The selected no_ca does not exist in account payable',
                'no_ca.unique' => 'The no_ca is already linked to another account payable',
            ]);
            if ($request->input('no_ca') !== null) {
                $checkNoCa = DB::table('account_payable')
                    ->where('no_accountpayable', $request->input('no_ca'))
                    ->first();
                if ($checkNoCa->type !== 'CA') {
                    throw new Exception('The no_ca must refer to an account payable of type CA');
                }

                $str = $request->input('no_ca');
                $parts = explode("-", $str);
                $angka = (int) $parts[1]; // hasil: 1001 (integer)


                // get just number no_ca
                $no_accountpayable = 'CAR-' . $angka;
                $no_ca = $request->input('no_ca');
            } else {
                $no_ca = null;
                $no_accountpayable = NumberHelper::generateAccountpayablenumber($request->input('type'));
            }

            // Calculate total upfront instead of updating later
            $total = collect($request->input('detail'))->sum('amount');

            $insertAccountpayable = DB::table('account_payable')->insertGetId([
                'no_accountpayable' => $no_accountpayable,
                'type' => $request->input('type') ?: null,
                'description' => $request->input('description') ?: null,
                'no_ca' => $no_ca,
                'total' => $total,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $id_position = Auth::user()->id_position;
            $id_division = Auth::user()->id_division;
            if (!$id_position || !$id_division) {
                throw new Exception('Invalid user position or division');
            }
            $flow_approval = DB::table('flowapproval_accountpayable')
                ->where(['request_position' => $id_position, 'request_division' => $id_division])
                ->first();
            if (!$flow_approval) {
                throw new Exception('No flow approval found for the user position and division');
            } else {
                $detail_flowapproval = DB::table('detailflowapproval_accountpayable')
                    ->where('id_flowapproval_accountpayable', $flow_approval->id_flowapproval_accountpayable)
                    ->get();
                foreach ($detail_flowapproval as $approval) {
                    $approval = [
                        'id_accountpayable' => $insertAccountpayable,
                        'approval_position' => $approval->approval_position,
                        'approval_division' => $approval->approval_division,
                        'step_no' => $approval->step_no,
                        'status' => 'pending',
                        'created_by' => Auth::id(),
                    ];
                    DB::table('approval_accountpayable')->insert($approval);
                }
            }

            // Prepare batch insert for details

            $attachments = [];
            foreach ($request->input('detail') as $detail) {
                $file_name = time() . '_' . $insertAccountpayable;

                // Decode the base64 image
                $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $detail['attachment']));
                $extension = explode('/', mime_content_type($detail['attachment']))[1];

                // Save file to public storage
                $path = 'accountpayable/' . $file_name . '.' . $extension;
                Storage::disk('public')->put($path, $image);

                // Ensure storage link exists
                if (!file_exists(public_path('storage'))) {
                    throw new Exception('Storage link not found. Please run "php artisan storage:link" command');
                }

                // Generate public URL that can be accessed directly
                $url = url('storage/' . $path);

                // Verify file was saved successfully
                if (!Storage::disk('public')->exists($path)) {
                    throw new Exception('Failed to save attachment to storage');
                }
                $detailRecords = [
                    'id_accountpayable' => $insertAccountpayable,
                    'type_pengeluaran' => $detail['type_pengeluaran'],
                    'description' => $detail['description'],
                    'amount' => $detail['amount'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $insertDetail = DB::table('detail_accountpayable')->insertGetId($detailRecords);
                if ($insertDetail) {
                    $attachments = [
                        'id_detailaccountpayable' => $insertDetail,
                        'file_name' => $file_name,
                        'url' => $url,
                        'public_id' => $file_name,
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $insertAttachment = DB::table('attachments_accountpayable')->insert($attachments);
                    if (!$insertAttachment) {
                        throw new Exception('Failed to save attachment record to database');
                    }
                }
            }



            DB::commit();
            return ResponseHelper::success('Account payable created successfully', NULL, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function getAccountpayable(Request $request)
    {
        $limit = $request->input('limit', 10);
        $searchKey = $request->input('searchKey', '');

        $accountPayables = DB::table('account_payable as ap')
            ->leftJoin('users as u', 'ap.created_by', '=', 'u.id_user')
            ->leftJoin('users as u2', 'ap.deleted_by', '=', 'u2.id_user')
            ->select(
                'ap.id_accountpayable',
                'ap.no_accountpayable',
                'ap.type',
                'ap.no_ca',
                'ap.total',
                'ap.created_at',
                'u.name as created_by',
                'ap.deleted_at',
                'ap.deleted_by',
                'u2.name as deleted_by_name'
            )
            ->when($searchKey, function ($query, $searchKey) {
                return $query->where(function ($q) use ($searchKey) {
                    $q->where('ap.no_accountpayable', 'like', '%' . $searchKey . '%')
                        ->orWhere('ap.type', 'like', '%' . $searchKey . '%')
                        ->orWhere('u.name', 'like', '%' . $searchKey . '%');
                });
            })
            ->where('created_by', Auth::id())
            ->orderBy('ap.created_at', 'desc')
            ->paginate($limit);


        $accountPayables->getCollection()->transform(function ($item) {
            $detail_accountpayable = DB::table('detail_accountpayable as d')
                ->select(
                    'd.id_detailaccountpayable',
                    'd.id_accountpayable',
                    'd.type_pengeluaran',
                    'tp.name as type_pengeluaran_name',
                    'd.description',
                    'd.amount',
                )
                ->join('type_pengeluaran as tp', 'd.type_pengeluaran', '=', 'tp.id_typepengeluaran')
                ->where('d.id_accountpayable', $item->id_accountpayable)
                ->get();

                $approval_accountpayable = DB::table('approval_accountpayable as a')
                ->select(
                    'a.id_approval_accountpayable',
                    'a.id_accountpayable',
                    'a.approval_position',
                    'p.name as approval_position_name',
                    'a.approval_division',
                    'd.name as approval_division_name',
                    'a.step_no',
                    'a.status',
                )
                ->join('positions as p', 'a.approval_position', '=', 'p.id_position')
                ->join('divisions as d', 'a.approval_division', '=', 'd.id_division')
                ->where('a.id_accountpayable', $item->id_accountpayable)
                ->orderBy('a.step_no', 'asc')
                ->get();
            $item->approval_accountpayable = $approval_accountpayable->where('id_accountpayable', $item->id_accountpayable)->values();

            $item->detail_accountpayable = $detail_accountpayable->where('id_accountpayable', $item->id_accountpayable)->values();
            return $item;
        });


        return ResponseHelper::success('Account payables retrieved successfully', $accountPayables, 200);
    }

    public function getAccountpayableById(Request $request)
    {

        DB::beginTransaction();
        $id = $request->input('id_accountpayable');
        try {
            $accountPayable = DB::table('account_payable as ap')
                ->leftJoin('users as u', 'ap.created_by', '=', 'u.id_user')
                ->leftJoin('users as u2', 'ap.deleted_by', '=', 'u2.id_user')
                ->select(
                    'ap.id_accountpayable',
                    'ap.no_accountpayable',
                    'ap.type',
                    'ap.no_ca',
                    'ap.total',
                    'ap.created_at',
                    'u.name as created_by',
                    'ap.deleted_at',
                    'ap.deleted_by',
                    'ap.description',
                    'u2.name as deleted_by_name'
                )
                ->where('ap.id_accountpayable', $id)
                ->first();

            if (!$accountPayable) {
                throw new Exception('Account payable not found');
            }

            $detail_accountpayable = DB::table('detail_accountpayable as d')
                ->select(
                    'd.id_detailaccountpayable',
                    'd.id_accountpayable',
                    'd.type_pengeluaran',
                    'tp.name as type_pengeluaran_name',
                    'd.description',
                    'd.amount',
                )
                ->join('type_pengeluaran as tp', 'd.type_pengeluaran', '=', 'tp.id_typepengeluaran')
                ->where('d.id_accountpayable', $id)
                ->get();

            $detail_accountpayable->transform(function ($item) {
                $attachment = DB::table('attachments_accountpayable')
                    ->where('id_detailaccountpayable', $item->id_detailaccountpayable)
                    ->first();
                $item->attachment = $attachment;
                return $item;
            });

            $accountPayable->detail_accountpayable = $detail_accountpayable;

            DB::commit();
            return ResponseHelper::success('Account payable retrieved successfully', $accountPayable, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function updateAccountpayable(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_accountpayable' => 'required|numeric|exists:account_payable,id_accountpayable',
                'type' => 'required|in:RE,PO,CA,CAR',
                'description' => 'required|string|max:255',
                'no_ca' => 'nullable|string|max:100|exists:account_payable,no_accountpayable',
                'detail' => 'required|array',
                'detail.*.id_detailaccountpayable' => 'nullable|numeric|exists:detail_accountpayable,id_detailaccountpayable',
                'detail.*.type_pengeluaran' => 'required|numeric|exists:type_pengeluaran,id_typepengeluaran',
                'detail.*.description' => 'required|string|max:255',
                'detail.*.amount' => 'required|numeric|min:0',
                'detail.*.attachment' => 'nullable|string',
            ]);
            $id_accountpayable = $request->input('id_accountpayable');
            $accountPayable = [
                'type' => $request->input('type'),
                'description' => $request->input('description'),
                'updated_by' => Auth::id(),
                'updated_at' => now(),
            ];
            if ($request->input('no_ca') !== null) {
                $checkNoCa = DB::table('account_payable')
                    ->where('no_accountpayable', $request->input('no_ca'))
                    ->first();
                if ($checkNoCa->type !== 'CA') {
                    throw new Exception('The no_ca must refer to an account payable of type CA');
                }
                DB::table('account_payable')
                    ->where('no_ca', $request->input('no_ca'))
                    ->update(['no_ca' => null]);
                $accountPayable['no_ca'] = $request->input('no_ca');
            }

            $updateAccountpayable = DB::table('account_payable')
                ->where('id_accountpayable', $id_accountpayable)
                ->update($accountPayable);

            foreach ($request->input('detail') as $key => $value) {
                if ($value['id_detailaccountpayable']) {
                    $detail = [
                        'type_pengeluaran' => $value['type_pengeluaran'],
                        'description' => $value['description'],
                        'amount' => $value['amount'],
                        'updated_by' => Auth::id(),
                        'updated_at' => now(),
                    ];
                    $updateDetail = DB::table('detail_accountpayable')
                        ->where('id_detailaccountpayable', $value['id_detailaccountpayable'])
                        ->update($detail);

                    if (isset($value['attachment'])) {
                        $attachment = DB::table('attachments_accountpayable')
                            ->where('id_detailaccountpayable', $value['id_detailaccountpayable'])
                            ->first();
                        // delete from storage
                        if ($attachment) {
                            $file_path = 'accountpayable/' . $attachment->file_name;
                            if (Storage::disk('public')->exists($file_path)) {
                                Storage::disk('public')->delete($file_path);
                            }
                            DB::table('attachments_accountpayable')
                                ->where('id_detailaccountpayable', $value['id_detailaccountpayable'])
                                ->delete();

                            $file_name = time() . '_' . $id_accountpayable;
                            // Decode the base64 image
                            $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $value['attachment']));
                            $extension = explode('/', mime_content_type($value['attachment']))[1];
                            // Save file to public storage
                            $path = 'accountpayable/' . $file_name . '.' . $extension;
                            Storage::disk('public')->put($path, $image);
                            // Ensure storage link exists
                            if (!file_exists(public_path('storage'))) {
                                throw new Exception('Storage link not found. Please run "php artisan storage:link" command');
                            }
                            // Generate public URL that can be accessed directly
                            $url = url('storage/' . $path);
                            // Verify file was saved successfully
                            if (!Storage::disk('public')->exists($path)) {
                                throw new Exception('Failed to save attachment to storage');
                            }
                            $attachments = [
                                'id_detailaccountpayable' => $value['id_detailaccountpayable'] ?? null,
                                'file_name' => $file_name,
                                'url' => $url,
                                'public_id' => $file_name,
                                'created_by' => Auth::id(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            $insertAttachment = DB::table('attachments_accountpayable')->insert($attachments);
                            if (!$insertAttachment) {
                                throw new Exception('Failed to save attachment record to database');
                            }
                        } else {
                            throw new Exception('Attachment record not found for the given detail');
                        }
                    }
                } else {
                    $detail = [
                        'id_detailaccountpayable' => $value['id_detailaccountpayable'] ?? null,
                        'type_pengeluaran' => $value['type_pengeluaran'],
                        'description' => $value['description'],
                        'amount' => $value['amount'],
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ];
                    $insertDetail = DB::table('detail_accountpayable')
                        ->insert($detail);
                    if ($insertDetail) {
                        if (isset($value['attachment'])) {
                            $file_name = time() . '_' . $id_accountpayable;
                            // Decode the base64 image
                            $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $value['attachment']));
                            $extension = explode('/', mime_content_type($value['attachment']))[1];
                            // Save file to public storage
                            $path = 'accountpayable/' . $file_name . '.' . $extension;
                            Storage::disk('public')->put($path, $image);
                            // Ensure storage link exists
                            if (!file_exists(public_path('storage'))) {
                                throw new Exception('Storage link not found. Please run "php artisan storage:link" command');
                            }
                            // Generate public URL that can be accessed directly
                            $url = url('storage/' . $path);
                            // Verify file was saved successfully
                            if (!Storage::disk('public')->exists($path)) {
                                throw new Exception('Failed to save attachment to storage');
                            }
                            $attachments = [
                                'id_detailaccountpayable' => $value['id_detailaccountpayable'] ?? null,
                                'file_name' => $file_name,
                                'url' => $url,
                                'public_id' => $file_name,
                                'created_by' => Auth::id(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            $insertAttachment = DB::table('attachments_accountpayable')->insert($attachments);
                            if (!$insertAttachment) {
                                throw new Exception('Failed to save attachment record to database');
                            }
                        } else {
                            throw new Exception('Attachment is required for new detail entries');
                        }
                    } else {
                        throw new Exception('Failed to insert new detail account payable');
                    }
                }
            }

            $total = DB::table('detail_accountpayable')
                ->where('id_accountpayable', $id_accountpayable)
                ->sum('amount');

            DB::table('account_payable')
                ->where('id_accountpayable', $id_accountpayable)
                ->update(['total' => $total]);

            DB::commit();
            return ResponseHelper::success('Account payable updated successfully', NULL, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteAccountpayable(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_accountpayable' => 'required|numeric|exists:account_payable,id_accountpayable',
            ]);

            $id_accountpayable = $request->input('id_accountpayable');

            $deleteAccountpayable = DB::table('account_payable')
                ->where('id_accountpayable', $id_accountpayable)
                ->update(['deleted_at' => now(), 'deleted_by' => Auth::id()]);

            if (!$deleteAccountpayable) {
                throw new Exception('Failed to delete account payable');
            }
            DB::commit();
            return ResponseHelper::success('Account payable deleted successfully', NULL, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function activateAccountpayable(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_accountpayable' => 'required|numeric|exists:account_payable,id_accountpayable',
            ]);

            $id_accountpayable = $request->input('id_accountpayable');

            $activateAccountpayable = DB::table('account_payable')
                ->where('id_accountpayable', $id_accountpayable)
                ->update(['deleted_at' => null, 'deleted_by' => null]);

            if (!$activateAccountpayable) {
                throw new Exception('Failed to activate account payable');
            }
            DB::commit();
            return ResponseHelper::success('Account payable activated successfully', NULL, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteDetailAccountpayable(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_detailaccountpayable' => 'required|numeric|exists:detail_accountpayable,id_detailaccountpayable',
            ]);

            $id_detailaccountpayable = $request->input('id_detailaccountpayable');

            $deleteDetailAccountpayable = DB::table('detail_accountpayable')
                ->where('id_detailaccountpayable', $id_detailaccountpayable)
                ->delete();

            if (!$deleteDetailAccountpayable) {
                throw new Exception('Failed to delete detail account payable');
            }
            $attachment = DB::table('attachments_accountpayable')
                ->where('id_detailaccountpayable', $id_detailaccountpayable)
                ->first();
            // delete from storage
            if ($attachment) {
                $file_path = 'accountpayable/' . $attachment->file_name;
                if (Storage::disk('public')->exists($file_path)) {
                    Storage::disk('public')->delete($file_path);
                }
                DB::table('attachments_accountpayable')
                    ->where('id_detailaccountpayable', $id_detailaccountpayable)
                    ->delete();
            }

            // Recalculate total in account_payable
            $detail = DB::table('detail_accountpayable')
                ->where('id_detailaccountpayable', $id_detailaccountpayable)
                ->first();

            if ($detail) {
                $total = DB::table('detail_accountpayable')
                    ->where('id_accountpayable', $detail->id_accountpayable)
                    ->sum('amount');

                DB::table('account_payable')
                    ->where('id_accountpayable', $detail->id_accountpayable)
                    ->update(['total' => $total]);
            }

            DB::commit();
            return ResponseHelper::success('Detail account payable deleted successfully', NULL, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
