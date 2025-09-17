<?php

namespace App\Http\Controllers\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Auth;

class DatacompanyController extends Controller
{
    public function createDataCompany(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'account_number' => 'required|numeric',
                'bank' => 'required|string|max:20',
                'branch' => 'required|string|max:255',
                'swift' => 'required|string|max:11',
            ]);

            $insert = DB::table('datacompany')->insert([
                'name' => $request->input('name'),
                'account_number' => $request->input('account_number'),
                'bank' => $request->input('bank'),
                'branch' => $request->input('branch'),
                'swift' => $request->input('swift'),
                'created_by' => Auth::id(),
            ]);
            if (!$insert) {
                throw new Exception('Failed to insert company data');
            }
            DB::commit();
            return ResponseHelper::success('Company data created successfully', NULL, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function getDataCompany(Request $request)
    {
        $limit = $request->input('limit', 10);
        $searchKey = $request->input('searchKey', '');

        $dataCompany = DB::table('datacompany')
        ->select([
            'datacompany.id_datacompany',
            'datacompany.name',
            'datacompany.account_number',
            'datacompany.bank',
            'datacompany.branch',
            'datacompany.swift',
            'datacompany.created_by',
            'users.name as created_by_name',
            'datacompany.status',
            'datacompany.deleted_at',   
            'datacompany.deleted_by',
            'u2.name as deleted_by_name'

        ])
        ->join('users', 'datacompany.created_by', '=', 'users.id_user')
        ->join('users as u2', 'datacompany.deleted_by', '=', 'u2.id_user', 'left')
            ->where('datacompany.name', 'like', '%' . $searchKey . '%')
            ->orWhere('datacompany.bank', 'like', '%' . $searchKey . '%')
            ->orWhere('datacompany.branch', 'like', '%' . $searchKey . '%')
            ->paginate($limit);

        return ResponseHelper::success('Company data retrieved successfully', $dataCompany, 200);
    }

    public function getDataCompanyById(Request $request)
    {
        try {
            $id = $request->input('id_datacompany');
            $dataCompany = DB::table('datacompany')
            ->select([
                'datacompany.id_datacompany',
                'datacompany.name',
                'datacompany.account_number',
                'datacompany.bank',
                'datacompany.branch',
                'datacompany.swift',
                'datacompany.created_by',
                'users.name as created_by_name',
                'datacompany.status',
                'datacompany.deleted_at',   
                'datacompany.deleted_by',
                'u2.name as deleted_by_name'
            ])
            ->join('users', 'datacompany.created_by', '=', 'users.id_user')
            ->join('users as u2', 'datacompany.deleted_by', '=', 'u2.id_user', 'left')
                ->where('datacompany.id_datacompany', $id)
                ->first();

            if (!$dataCompany) {
                return ResponseHelper::success('Company data not found', null, 404);
            }

            return ResponseHelper::success('Company data retrieved successfully', $dataCompany, 200);
        } catch (Exception $e) {
            return ResponseHelper::error($e);
        }
    }

    public function updateDataCompany(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_datacompany' => 'required|integer|exists:datacompany,id_datacompany',
                'name' => 'required|string|max:255',
                'account_number' => 'required|numeric',
                'bank' => 'required|string|max:20',
                'branch' => 'required|string|max:255',
                'swift' => 'required|string|max:11',
            ]);

            $changes = [];

            $dataCompany = DB::table('datacompany')
                ->where('id_datacompany', $request->input('id_datacompany'))
                ->first();

            $update = DB::table('datacompany')
                ->where('id_datacompany', $request->input('id_datacompany'))
                ->update([
                    'name' => $request->input('name'),
                    'account_number' => $request->input('account_number'),
                    'bank' => $request->input('bank'),
                    'branch' => $request->input('branch'),
                    'swift' => $request->input('swift'),
                ]);
               $changes = [
                'before' => $dataCompany,
                'after' => [
                    'name' => $request->input('name'),
                    'account_number' => $request->input('account_number'),
                    'bank' => $request->input('bank'),
                    'branch' => $request->input('branch'),
                    'swift' => $request->input('swift'),
                ]
               ];

               $insertToLog = DB::table('log_datacompany')->insert([
                   'id_datacompany' => $request->input('id_datacompany'),
                   'action' => json_encode($changes),
                   'created_by' => Auth::id(),
               ]);
               if (!$insertToLog) {
                throw new Exception('Failed to insert log data company');
               }

            DB::commit();
            return ResponseHelper::success('Company data updated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteDatacompany(Request $request) {
        DB::beginTransaction();
        try {
            $id = $request->input('id_datacompany');
            $dataCompany = DB::table('datacompany')
                ->where('id_datacompany', $id)
                ->where('deleted_at', null)
                ->first();

            if (!$dataCompany) {
                return ResponseHelper::success('Company data not found', null, 404);
            }

            $delete = DB::table('datacompany')
                ->where('id_datacompany', $id)
                ->update([
                    'deleted_at' => now(),
                    'deleted_by' => Auth::id(),
                ]);

            if (!$delete) {
                throw new Exception('Failed to delete company data');
            }

            $insertToLog = DB::table('log_datacompany')->insert([
                'id_datacompany' => $id,
                'action' => json_encode([
                    'before' => $dataCompany,
                    'after' => [
                        'deleted_at' => now(),
                        'deleted_by' => Auth::id(),
                    ]
                ]),
                'created_by' => Auth::id(),
            ]);
            if (!$insertToLog) {
                throw new Exception('Failed to insert log data company');
            }

            DB::commit();
            return ResponseHelper::success('Company data deleted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function activateDatacompany(Request $request) {
        DB::beginTransaction();
        try {
            $id = $request->input('id_datacompany');
            $dataCompany = DB::table('datacompany')
                ->where('id_datacompany', $id)
                ->where('deleted_at', '!=', null)
                ->first();

            if (!$dataCompany) {
                return ResponseHelper::success('Company data not found', null, 404);
            }

            $activate = DB::table('datacompany')
                ->where('id_datacompany', $id)
                ->update([
                    'deleted_at' => null,
                    'deleted_by' => null,
                ]);

            if (!$activate) {
                throw new Exception('Failed to activate company data');
            }

            $insertToLog = DB::table('log_datacompany')->insert([
                'id_datacompany' => $id,
                'action' => json_encode([
                    'before' => $dataCompany,
                    'after' => [
                        'deleted_at' => null,
                        'deleted_by' => null,
                    ]
                ]),
                'created_by' => Auth::id(),
            ]);
            if (!$insertToLog) {
                throw new Exception('Failed to insert log data company');
            }

            DB::commit();
            return ResponseHelper::success('Company data activated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
