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
                'agent' => 'required|exists:customer,id_customer',
                'data_agent' => 'required|exists:data_customer,id_datacustomer',
                'due_date' => 'required|date',
                'remarks' => 'nullable|string|max:255',
                'id_datacompany' => 'required|exists:datacompany,id_datacompany',
                'jobsheet' => 'nullable|array',
                'jobsheet.*.id_jobsheet' => 'required|exists:jobsheets,id_jobsheet',
            ]);
            $no_invoice = NumberHelper::generateInvoiceNumber();
            $invoice = [
                'agent' => $request->input('agent'),
                'data_agent' => $request->input('data_agent'),
                'no_invoice' => $no_invoice,
                'invoice_date' => $request->input('invoice_date'),
                'due_date' => $request->input('due_date'),
                'remarks' => $request->input('remarks'),
                'id_datacompany' => $request->input('id_datacompany'),
                'created_by' => Auth::id(),
            ];
            $insertInvoice = DB::table('invoice')->insertGetId($invoice);
            foreach ($request->input('jobsheet', []) as $jobsheet) {
                $jobsheet = DB::table('jobsheet')->where('id_jobsheet', $jobsheet['id_jobsheet'])->first();
                $so = DB::table('salesorder')->where('id_salesorder', $jobsheet->id_salesorder)->first();
                $awb = DB::table('awb')->where('id_awb', $jobsheet->id_awb)->first();
                $insertDetailInvoice = DB::table('detail_invoice')->insert([
                    'id_invoice' => $insertInvoice,
                    'id_jobsheet' => $jobsheet['id_jobsheet'],
                    'id_salesorder' => $so->id_salesorder,
                    'id_awb' => $awb->id_awb,
                ]);
                if (!$insertDetailInvoice) {
                    throw new Exception('Failed to insert detail invoice');
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }

    }

    public function getInvoice(Request $request) {
        $limit = $request->input('limit', 10);
        $searchKey = $request->input('searchKey', '');

        $select = [
            'a.id_invoice',
            'a.agent',
            'e.name AS agent_name',
            'a.data_agent',
            'f.pic',
            'f.email',
            'f.phone',
            'f.tax_id',
            'f.address',
            'a.no_invoice',
            'a.invoice_date',
            'a.due_date',
            'a.remarks',
            'a.created_at',
            'a.created_by',
            'b.name AS created_by_name',
            'a.deleted_at',
            'a.deleted_by',
            'c.name AS deleted_by_name',
            'a.id_datacompany',
            'd.name AS datacompany_name',
            'd.account_number AS datacompany_account_number',
            'd.bank AS datacompany_bank',
            'd.branch AS datacompany_branch',
            'd.swift AS datacompany_swift',
            'a.status'
        ];

        $invoices = DB::table('invoice AS a')
            ->select($select)
            ->leftJoin('users AS b', 'a.created_by', '=', 'b.id_user')
            ->leftJoin('users AS c', 'a.deleted_by', '=', 'c.id_user')
            ->leftJoin('datacompany AS d', 'a.id_datacompany', '=', 'd.id_datacompany')
            ->leftJoin('customer AS e', 'a.agent', '=', 'e.id_customer')
            ->leftJoin('data_customer AS f', 'a.data_agent', '=', 'f.id_datacustomer')
            ->where('a.deleted_at', null)
            ->where(function($query) use ($searchKey) {
                $query->where('a.no_invoice', 'LIKE', "%{$searchKey}%")
                      ->orWhere('a.remarks', 'LIKE', "%{$searchKey}%");
            })
            ->paginate($limit);

        return ResponseHelper::success('Invoices retrieved successfully', $invoices);
    }
}
