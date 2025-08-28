<?php

namespace App\Http\Controllers\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Auth;

class OtherchargesinvoiceController extends Controller
{
    public function createOtherchargesinvoice(Request $request)  {
        $request->validate([

            'name' => 'required|string|max:255|unique:listothercharge_invoice,name',
            'type' => 'required|in:percentage_subtotal,multiple_awb,multiple_chargeableweight,multiple_grossweight,nominal',
        ]);

        DB::beginTransaction();

        try {
            $insert = DB::table('listothercharge_invoice')->insert([
                'name' => $request->input('name'),
                'type' => $request->input('type'),
                'created_at' => now(),
                'created_by' => Auth::id(),

            ]);

            if (!$insert) {
                throw new Exception('Failed to create other charges invoice');
            }

            DB::commit();
            return ResponseHelper::success('Other charges invoice created successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
    

    public function getOtherchargesinvoice(Request $request) {
        $limit = $request->input('limit', 10);
        $searchKey = $request->input('searchKey', '');

        $select = [
            'a.id_listothercharge_invoice',
            'a.name',
            'a.type',
            'a.created_at',
            'a.created_by',
            'u.name AS created_by_name'
        ];

        $otherCharges = DB::table('listothercharge_invoice AS a')
            ->select($select)
            ->join('users AS u', 'a.created_by', '=', 'u.id_user')
            ->where(function($query) use ($searchKey) {
                $query->where('a.name', 'LIKE', "%{$searchKey}%");
            })
            ->paginate($limit);

        return ResponseHelper::success('Other charges retrieved successfully', $otherCharges);
    }

    public function getOtherchargesinvoiceById(Request $request) {
        $request->validate([
            'id_listothercharge_invoice' => 'required|integer|exists:listothercharge_invoice,id_listothercharge_invoice',
        ]);

        $otherCharge = DB::table('listothercharge_invoice AS a')
            ->select('a.*', 'u.name AS created_by_name')
            ->join('users AS u', 'a.created_by', '=', 'u.id_user')
            ->where('a.id_listothercharge_invoice', $request->input('id_listothercharge_invoice'))
            ->first();

        if (!$otherCharge) {
            return ResponseHelper::success('Other charge not found');
        } else{
            return ResponseHelper::success('Other charge retrieved successfully', $otherCharge);
        }
    }

    public function updateOtherchargesinvoice(Request $request) {
        DB::beginTransaction();

        try {
            $request->validate([
                'id_listothercharge_invoice' => 'required|integer|exists:listothercharge_invoice,id_listothercharge_invoice',
                'name' => 'required|string|max:255',
                'type' => 'required|in:percentage_subtotal,multiple_awb,multiple_chargeableweight,multiple_grossweight,nominal',
            ]);

            $update = DB::table('listothercharge_invoice')
                ->where('id_listothercharge_invoice', $request->input('id_listothercharge_invoice'))
                ->update([
                    'name' => $request->input('name'),
                    'type' => $request->input('type'),
                ]);

            if (!$update) {
                throw new Exception('Failed to update other charges invoice');
            }

            DB::commit();
            return ResponseHelper::success('Other charges invoice updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteOtherchargesinvoice(Request $request)  {
        DB::beginTransaction();

        try {
            $request->validate([
                'id_listothercharge_invoice' => 'required|integer|exists:listothercharge_invoice,id_listothercharge_invoice',
            ]);
            

            $delete = DB::table('listothercharge_invoice')
                ->where('id_listothercharge_invoice', $request->input('id_listothercharge_invoice'))
                ->update(['deleted_at' => now(), 'deleted_by' => Auth::id()]);

            if (!$delete) {
                throw new Exception('Failed to delete other charges invoice');
            }

            DB::commit();
            return ResponseHelper::success('Other charges invoice deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function activateOtherchargesinvoice(Request $request) {
        DB::beginTransaction();

        try {
            $request->validate([
                'id_listothercharge_invoice' => 'required|integer|exists:listothercharge_invoice,id_listothercharge_invoice',
            ]);

            $activate = DB::table('listothercharge_invoice')
                ->where('id_listothercharge_invoice', $request->input('id_listothercharge_invoice'))
                ->update(['deleted_at' => null, 'deleted_by' => null]);

            if (!$activate) {
                throw new Exception('Failed to activate other charges invoice');
            }

            DB::commit();
            return ResponseHelper::success('Other charges invoice activated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
