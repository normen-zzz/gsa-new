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

            $insert = DB::table('company')->insert([
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

        $dataCompany = DB::table('company')
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
            $dataCompany = DB::table('company')
                ->where('id_company', $id)
                ->where('deleted_at', null)
                ->first();

            if (!$dataCompany) {
                return ResponseHelper::error('Company data not found', null, 404);
            }

            return ResponseHelper::success('Company data retrieved successfully', $dataCompany, 200);
        } catch (Exception $e) {
            return ResponseHelper::error($e);
        }
    }
}
