<?php

namespace App\Http\Controllers\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Helpers\ResponseHelper;

class CompanyController extends Controller
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
                'swift_code' => 'required|string|max:11',
            ]);

            $insert = DB::table('datacompany')->insert([
                'name' => $request->input('name'),
                'account_number' => $request->input('account_number'),
                'bank' => $request->input('bank'),
                'branch' => $request->input('branch'),
                'swift_code' => $request->input('swift_code'),
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

        $select = [
            'a.id_datacompany',
            'a.name',
            'a.account_number',
            'a.bank',
            'a.branch',
            'a.swift',
            'a.created_at',
            'a.created_by',
            'b.name AS created_by_name',
            'a.deleted_at',
            'a.deleted_by',
            'c.name AS deleted_by_name',
            'a.status'
        ];
        $dataCompany = DB::table('datacompany AS a')
        ->join('users AS b', 'a.created_by', '=', 'b.id_user')
        ->leftJoin('users AS c', 'a.deleted_by', '=', 'c.id_user')
            ->where('deleted_at', null)
            ->where('name', 'like', '%' . $searchKey . '%')
            ->orWhere('bank', 'like', '%' . $searchKey . '%')
            ->orWhere('branch', 'like', '%' . $searchKey . '%')
            ->paginate($limit);

        return ResponseHelper::success('Company data retrieved successfully', $dataCompany, 200);
    }

    public function getDataCompanyById(Request $request)
    {
        try {
            $id = $request->input('id_company');
            $select = [
                'a.id_datacompany',
                'a.name',
                'a.account_number',
                'a.bank',
                'a.branch',
                'a.swift',
                'a.created_at',
                'a.created_by',
                'b.name AS created_by_name',
                'a.deleted_at',
                'a.deleted_by',
                'c.name AS deleted_by_name',
                'a.status'
            ];
            $dataCompany = DB::table('datacompany AS a')
                ->select($select)
            ->join('users AS b', 'a.created_by', '=', 'b.id_user')
            ->leftJoin('users AS c', 'a.deleted_by', '=', 'c.id_user')
                ->where('id_company', $id)
                ->where('deleted_at', null)
                ->first();

            if (!$dataCompany) {
                return ResponseHelper::success('Company data not found', null, 404);
            }

            return ResponseHelper::success('Company data retrieved successfully', $dataCompany, 200);
        } catch (Exception $e) {
            return ResponseHelper::error($e);
        }
    }
}
