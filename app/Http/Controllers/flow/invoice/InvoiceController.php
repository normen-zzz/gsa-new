<?php

namespace App\Http\Controllers\flow\invoice;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function createInvoice(Request $request) {
        DB::beginTransaction();
        try {
            $request->validate([
                'due_date' => 'required|date',
                'jobsheet' => 'nullable|array',
                'jobsheet.*.id_jobsheet' => 'required|exists:jobsheets,id_jobsheet',
                
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }

    }
}
