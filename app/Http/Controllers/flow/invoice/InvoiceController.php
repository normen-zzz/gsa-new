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
    public function createInvoice(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'agent' => 'required|exists:customers,id_customer',
                'data_agent' => 'required|exists:data_customer,id_datacustomer',
                'due_date' => 'required|date',
                'remarks' => 'nullable|string|max:255',
                'id_datacompany' => 'required|exists:datacompany,id_datacompany',
                'jobsheet' => 'nullable|array',
                'jobsheet.*.id_jobsheet' => 'required|exists:jobsheet,id_jobsheet',
                'others' => 'nullable|array',
                'others.*.id_listothercharge_invoice' => 'required|exists:listothercharge_invoice,id_listothercharge_invoice',
                'others.*.amount' => 'required|numeric|min:0',
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
                    'id_jobsheet' => $jobsheet->id_jobsheet,
                    'id_salesorder' => $so->id_salesorder,
                    'id_awb' => $awb->id_awb,
                ]);
                if (!$insertDetailInvoice) {
                    throw new Exception('Failed to insert detail invoice');
                }
            }

            $user = DB::table('users')->where('id_user', Auth::id())->first();
            $approval = DB::table('flowapproval_invoice')->where(
                [
                    'request_position' => $user->id_position,
                    'request_division' => $user->id_division,
                ]
            )->first();

            $detailApproval = DB::table('detailflowapproval_invoice')
                ->where('id_flowapproval_invoice', $approval->id_flowapproval_invoice)
                ->get();

            if ($detailApproval) {
                foreach ($detailApproval as $app) {
                    DB::table('approval_invoice')->insert([
                        'id_invoice' => $insertInvoice,
                        'approval_position' => $app->approval_position,
                        'approval_division' => $app->approval_division,
                        'step_no' => $app->step_no,
                        'status' => 'pending',
                        'created_at' => now(),
                        'created_by' => Auth::id(),
                    ]);
                }
            } else {
                throw new Exception('No approval flow found');
            }

            $othersCharge = $request->input('others');
            foreach ($othersCharge as $charge) {
                $insertOthersCharge = DB::table('otherscharge_invoice')->insert([
                    'id_invoice' => $insertInvoice,
                    'id_listothercharge_invoice' => $charge['id_listothercharge_invoice'],
                    'amount' => $charge['amount'],
                ]);
                if (!$insertOthersCharge) {
                    throw new Exception('Failed to insert others charge');
                }
            }

            DB::commit();
            return ResponseHelper::success('Invoice created successfully', ['id_invoice' => $insertInvoice]);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function getInvoice(Request $request)
    {
        $limit = $request->input('limit', 10);
        $searchKey = $request->input('searchKey', '');

        $select = [
            'a.id_invoice',
            'a.agent',
            'e.name_customer AS agent_name',
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
            ->leftJoin('customers AS e', 'a.agent', '=', 'e.id_customer')
            ->leftJoin('data_customer AS f', 'a.data_agent', '=', 'f.id_datacustomer')
            ->where('a.deleted_at', null)
            ->where(function ($query) use ($searchKey) {
                $query->where('a.no_invoice', 'LIKE', "%{$searchKey}%")
                    ->orWhere('a.remarks', 'LIKE', "%{$searchKey}%");
            })
            ->paginate($limit);

        foreach ($invoices as $key => $value) {
            $detailInvoice = DB::table('detail_invoice')
                ->where('id_invoice', $value->id_invoice)
                ->get();

            $invoices[$key]->detail_invoice = $detailInvoice;

            $approval = DB::table('approval_invoice')
                ->where('id_invoice', $value->id_invoice)
                ->get();

            $invoices[$key]->approval_invoice = $approval;

            $othersCharge = DB::table('otherscharge_invoice AS oci')
                ->select('oci.*', 'l.name AS charge_name', 'l.type AS charge_type')
                ->leftJoin('listothercharge_invoice AS l', 'oci.id_listothercharge_invoice', '=', 'l.id_listothercharge_invoice')
                ->where('oci.id_invoice', $value->id_invoice)
                ->get();

            $invoices[$key]->others_charge = $othersCharge;
        }

        return ResponseHelper::success('Invoices retrieved successfully', $invoices);
    }

    public function getInvoiceById(Request $request)
    {
        $request->validate([
            'id_invoice' => 'required|exists:invoice,id_invoice',
        ]);

        $select = [
            'a.id_invoice',
            'a.agent',
            'e.name_customer AS agent_name',
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

        $invoice = DB::table('invoice AS a')
            ->select($select)
            ->leftJoin('users AS b', 'a.created_by', '=', 'b.id_user')
            ->leftJoin('users AS c', 'a.deleted_by', '=', 'c.id_user')
            ->leftJoin('datacompany AS d', 'a.id_datacompany', '=', 'd.id_datacompany')
            ->leftJoin('customers AS e', 'a.agent', '=', 'e.id_customer')
            ->leftJoin('data_customer AS f', 'a.data_agent', '=', 'f.id_datacustomer')
            ->where('a.id_invoice', $request->input('id_invoice'))
            ->where('a.deleted_at', null)
            ->first();

        if (!$invoice) {
            return ResponseHelper::success('Invoice not found');
        }

        $detailInvoice = DB::table('detail_invoice')
            ->where('id_invoice', $invoice->id_invoice)
            ->get();

        $invoice->detail_invoice = $detailInvoice;

        $approval = DB::table('approval_invoice')
            ->where('id_invoice', $invoice->id_invoice)
            ->get();

        $invoice->approval_invoice = $approval;

        $othersCharge = DB::table('otherscharge_invoice AS oci')
            ->select('oci.*', 'l.name AS charge_name', 'l.type AS charge_type')
            ->leftJoin('listothercharge_invoice AS l', 'oci.id_listothercharge_invoice', '=', 'l.id_listothercharge_invoice')
            ->where('oci.id_invoice', $invoice->id_invoice)
            ->get();

        $invoice->others_charge = $othersCharge;

        return ResponseHelper::success('Invoice retrieved successfully', $invoice);
    }

    public function updateInvoice(Request $request)
    {
        $request->validate([
            'id_invoice' => 'required|exists:invoice,id_invoice',
            'agent' => 'required|exists:customers,id_customer',
            'data_agent' => 'required|exists:data_customer,id_datacustomer',
            'due_date' => 'required|date',
            'remarks' => 'nullable|string|max:255',
            'id_datacompany' => 'required|exists:datacompany,id_datacompany',
            'jobsheet' => 'nullable|array',
            'jobsheet.*.id_jobsheet' => 'required|exists:jobsheet,id_jobsheet',
            'others' => 'nullable|array',
            'others.*.id_listothercharge_invoice' => 'required',
            'others.*.amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $invoice = DB::table('invoice')
                ->where('id_invoice', $request->input('id_invoice'))
                ->update([
                    'agent' => $request->input('agent'),
                    'data_agent' => $request->input('data_agent'),
                    'no_invoice' => $request->input('no_invoice'),
                    'invoice_date' => $request->input('invoice_date'),
                    'due_date' => $request->input('due_date'),
                    'remarks' => $request->input('remarks'),
                    'updated_at' => now(),
                    'updated_by' => Auth::id(),
                ]);
            if ($invoice) {
                if ($request->input('jobsheet')) {
                    foreach ($request->input('jobsheet') as $jobsheet1 => $value) {
                        $dataJobsheet = DB::table('jobsheet')->where('id_jobsheet', $value['id_jobsheet'])->first();
                        $insertJobsheet = DB::table('detail_invoice')->insert(
                            [
                                'id_invoice' => $request->input('id_invoice'),
                                'id_jobsheet' => $dataJobsheet->id_jobsheet,
                                'id_salesorder' => $dataJobsheet->id_salesorder,
                                'id_awb' => $dataJobsheet->id_awb,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        if (!$insertJobsheet) {
                            throw new Exception('Failed to insert jobsheet to detail invoice');
                        }
                    }
                }

                if ($request->input('others')) {
                    foreach ($request->input('others') as $other) {
                        $insertOther = DB::table('otherscharge_invoice')->insert(
                            [
                                'id_invoice' => $request->input('id_invoice'),
                                'id_listothercharge_invoice' => $other['id_listothercharge_invoice'],
                                'amount' => $other['amount'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        if (!$insertOther) {
                            throw new Exception('Failed to insert other charge to invoice');
                        }
                    }
                }
            }

            DB::commit();
            return ResponseHelper::success('Invoice updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteDetailinvoice(Request $request)
    {

        DB::beginTransaction();
        try {
            $request->validate([
                'id_detail_invoice' => 'required|exists:detail_invoice,id_detail_invoice',
            ]);
            $delete = DB::table('detail_invoice')
                ->where('id_detail_invoice', $request->input('id_detail_invoice'))
                ->delete();
            if (!$delete) {
                throw new Exception('Failed to delete detail invoice');
            }
            DB::commit();
            return ResponseHelper::success('Detail invoice deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteOtherschargeinvoice(Request $request)  {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_otherscharge_invoice' => 'required|exists:otherscharge_invoice,id_otherscharge_invoice',
            ]);
            $delete = DB::table('otherscharge_invoice')
                ->where('id_otherscharge_invoice', $request->input('id_otherscharge_invoice'))
                ->delete();
            if (!$delete) {
                throw new Exception('Failed to delete other charge invoice');
            }
            DB::commit();
            return ResponseHelper::success('Other charge invoice deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
