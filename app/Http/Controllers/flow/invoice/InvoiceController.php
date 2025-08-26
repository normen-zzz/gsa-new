<?php

namespace App\Http\Controllers\flow\invoice;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\NumberHelper;
use Illuminate\Support\Facades\Auth;

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
            $no_invoice = NumberHelper::generateInvoiceNumber();
            $invoice = [
                'agent' => $request->input('agent'),
                'data_agent' => $request->input('data_agent'),
                'invoice_number' => $no_invoice,
                'invoice_date' => $request->input('invoice_date'),
                'due_date' => $request->input('due_date'),
                'remarks' => $request->input('remarks'),
                'created_by' => Auth::id(),
            ];
            $insertInvoice = DB::table('invoice')->insertGetId($invoice);
            foreach ($request->input('jobsheet', []) as $jobsheet) {
                $jobsheet = DB::table('jobsheet')->where('id_jobsheet', $jobsheet['id_jobsheet'])->first();
                $so = DB::table('salesorder')->where('id_salesorder', $jobsheet->id_salesorder)->first();
                $awb = DB::table('awb')->where('id_awb', $jobsheet->id_awb)->first();
                DB::table('detail_invoice')->insert([
                    'id_invoice' => $insertInvoice,
                    'id_jobsheet' => $jobsheet['id_jobsheet'],
                    'id_salesorder' => $so->id_salesorder,
                    'id_awb' => $awb->id_awb,
                ]);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }

    }
}
