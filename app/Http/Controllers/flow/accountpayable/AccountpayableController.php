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

class AccountpayableController extends Controller
{
    public function createAccountpayable(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'type' => 'required|in:RE,PO,CA,CAR',
                'description' => 'required|string|max:255',
                'no_ca' => 'nullable|string|max:100|exists:account_payable,no_accountpayable',
                'detail' => 'required|array',
                'detail.*.type_pengeluaran' => 'required|numeric|exists:type_pengeluaran,id_typepengeluaran',
                'detail.*.description' => 'required|string|max:255',
                'detail.*.amount' => 'required|numeric|min:0',
            ]);



            $no_accountpayable = NumberHelper::generateAccountpayablenumber($request->input('type'));
            if ($request->input('no_ca') !== null) {

                $checkNoCa = DB::table('account_payable')
                    ->where('no_accountpayable', $request->input('no_ca'))
                    ->first();
                if ($checkNoCa->type !== 'CA') {
                    throw new Exception('The no_ca must refer to an account payable of type CA');
                }

                $checkUniqueCa = DB::table('account_payable')
                    ->where('no_ca', $request->input('no_ca'))
                    ->first();
                if ($checkUniqueCa) {
                    throw new Exception('The no_ca is already linked to another account payable');
                }
                $str = $request->input('no_ca');
                $parts = explode("-", $str);
                $angka = (int) $parts[1]; // hasil: 1001 (integer)


                // get just number no_ca
                $no_accountpayable = 'CAR-' . $angka;
            }

            // Calculate total upfront instead of updating later
            $total = collect($request->input('detail'))->sum('amount');

            $insertAccountpayable = DB::table('account_payable')->insertGetId([
                'no_accountpayable' => $no_accountpayable,
                'type' => $request->input('type') ?: null,
                'description' => $request->input('description') ?: null,
                'total' => $total,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Prepare batch insert for details
            $detailRecords = [];
            foreach ($request->input('detail') as $detail) {
                $detailRecords[] = [
                    'id_accountpayable' => $insertAccountpayable,
                    'type_pengeluaran' => $detail['type_pengeluaran'],
                    'description' => $detail['description'],
                    'amount' => $detail['amount'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Batch insert all details at once
            if (!DB::table('detail_accountpayable')->insert($detailRecords)) {
                throw new Exception('Failed to create account payable details');
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
                } else {
                    $detail = [
                        'id_accountpayable' => $id_accountpayable,
                        'type_pengeluaran' => $value['type_pengeluaran'],
                        'description' => $value['description'],
                        'amount' => $value['amount'],
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ];
                    $insertDetail = DB::table('detail_accountpayable')
                        ->insert($detail);
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
}
