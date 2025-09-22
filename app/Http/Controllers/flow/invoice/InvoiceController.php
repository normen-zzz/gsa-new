<?php

namespace App\Http\Controllers\flow\invoice;

use Exception;
use Illuminate\Http\Request;
use App\Helpers\NumberHelper;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Riskihajar\Terbilang\Facades\Terbilang;


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
                'updated_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
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
            'a.status_invoice',
            'a.status_approval'
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
            $pendingApproval = DB::table('approval_invoice')
                ->where('id_invoice', $value->id_invoice)
                ->where('status', 'pending')
                ->orderBy('step_no', 'ASC')
                ->first();

            $position = Auth::user()->id_position;
            $division = Auth::user()->id_division;
            if ($pendingApproval && $pendingApproval->approval_position == $position && $pendingApproval->approval_division == $division) {
                $invoices[$key]->is_approver = true;
                $invoices[$key]->id_approval_invoice = $pendingApproval->id_approval_invoice;
            } else {
                $invoices[$key]->is_approver = false;
            }
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
            'a.status_invoice',
            'a.status_approval'
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

        $selectDetail = [
            'detail_invoice.id_detail_invoice',
            'detail_invoice.id_invoice',
            'detail_invoice.id_jobsheet',
            'detail_invoice.id_salesorder',
            'detail_invoice.id_awb',
            'a.name AS airline_name',
            'a.code AS airline_code',
            'awb.awb',
            'awb.pol',
            'pol.name_airport AS pol_name',
            'pol.code_airport AS pol_code',
            'awb.pod',
            'pod.name_airport AS pod_name',
            'pod.code_airport AS pod_code',
            'awb.etd',
            'awb.gross_weight',
            'awb.chargeable_weight',
            'awb.pieces'

        ];
        $detailInvoice = DB::table('detail_invoice')
            ->select($selectDetail)
            ->join('awb', 'detail_invoice.id_awb', '=', 'awb.id_awb')
            ->join('airports AS pol', 'awb.pol', '=', 'pol.id_airport')
            ->join('airports AS pod', 'awb.pod', '=', 'pod.id_airport')
            ->join('airlines AS a', 'awb.airline', '=', 'a.id_airline')
            ->where('id_invoice', $invoice->id_invoice)
            ->get();


        $total_selling = 0;
        foreach ($detailInvoice as $key => $value) {
            $selectSelling = [
                'ts.name AS typeselling_name',
                'ts.initials AS typeselling_initials',
                'selling_salesorder.selling_value',
                'selling_salesorder.charge_by',
            ];

            $awb = DB::table('awb')
                ->where('id_awb', $value->id_awb)
                ->first();

            $data_selling_salesorder = [];
            $selling_salesorder = DB::table('selling_salesorder')
                ->select($selectSelling)
                ->join('typeselling AS ts', 'selling_salesorder.id_typeselling', '=', 'ts.id_typeselling')
                ->where('id_salesorder', $value->id_salesorder)
                ->get();

            foreach ($selling_salesorder as $sell) {
                switch ($sell->charge_by) {
                    case 'chargeable_weight':
                        $weight = $awb->chargeable_weight;
                        $total = $sell->selling_value * $weight;
                        break;
                    case 'gross_weight':
                        $weight = $awb->gross_weight;
                        $total = $sell->selling_value * $weight;
                        break;
                    case 'awb':
                        $weight = 1;
                        $total = $sell->selling_value * $weight;
                        break;

                    default:
                        $total = $sell->selling_value;
                        break;
                }
                $total_selling += $total;
                $data_selling_salesorder[] = [
                    'initials' => $sell->typeselling_initials,
                    'selling_value' => $sell->selling_value,
                    'charge_by' => $sell->charge_by,
                    'weight' => $weight,
                    'amount' => $total,
                ];
            }
            $detailInvoice[$key]->selling_salesorder = $data_selling_salesorder;
        }

        $invoice->detail_invoice = $detailInvoice;
        $subtotal = $total_selling;
        $invoice->subtotal = $subtotal;




        $othersCharge = DB::table('otherscharge_invoice AS oci')
            ->select('oci.*', 'l.name AS charge_name', 'l.type AS charge_type')
            ->leftJoin('listothercharge_invoice AS l', 'oci.id_listothercharge_invoice', '=', 'l.id_listothercharge_invoice')
            ->where('oci.id_invoice', $invoice->id_invoice)
            ->get();

        $dataOtherscharge = [];


        foreach ($othersCharge as $charge) {
            $multiplier = 0;
            switch ($charge->charge_type) {
                case 'percentage_subtotal':
                    $multiplier = $total_selling;
                    $total = ($charge->amount / 100) * $multiplier;
                    $total_selling += $total;
                    break;
                case 'nominal':
                    $multiplier = 1;
                    $total = $charge->amount * $multiplier;
                    $total_selling += $total;
                    break;
                case 'multiple_chargeableweight':
                    $multiplier = $awb->chargeable_weight;
                    $total = $charge->amount * $multiplier;
                    $total_selling += $total;
                    break;
                case 'multiple_grossweight':
                    $multiplier = $awb->gross_weight;
                    $total   = $charge->amount * $multiplier;
                    $total_selling += $total;
                    break;

                default:
                    $multiplier = 1;
                    $total = $charge->amount * $multiplier;
                    $total_selling += $total;
                    break;
            }
            $dataOtherscharge[] = [
                'id_otherscharge_invoice' => $charge->id_otherscharge_invoice,
                'id_listothercharge_invoice' => $charge->id_listothercharge_invoice,
                'charge_name' => $charge->charge_name,
                'charge_type' => $charge->charge_type,
                'amount' => $charge->amount,
                'total' => $total
            ];
        }

        $invoice->others_charge = $dataOtherscharge;

        $invoice->grand_total = $total_selling;
        Config::set('terbilang.locale', 'en');
        // "one million ... dollars"
        $terbilang = strtoupper(Terbilang::make($invoice->grand_total, ' rupiahs'));
        $invoice->said = $terbilang;
        $approval = DB::table('approval_invoice')
            ->where('id_invoice', $invoice->id_invoice)
            ->get();

        $invoice->approval_invoice = $approval;
        $pendingApproval = DB::table('approval_invoice')
            ->where('id_invoice', $invoice->id_invoice)
            ->where('status', 'pending')
            ->orderBy('step_no', 'ASC')
            ->first();

        $position = Auth::user()->id_position;
        $division = Auth::user()->id_division;
        if ($pendingApproval && $pendingApproval->approval_position == $position && $pendingApproval->approval_division == $division) {
            $invoice->is_approver = true;
            $invoice->id_approval_invoice = $pendingApproval->id_approval_invoice;
        } else {
            $invoice->is_approver = false;
        }





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

    public function deleteOtherschargeinvoice(Request $request)
    {
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

    public function actionInvoice(Request $request)
    {

        DB::beginTransaction();
        try {
            $request->validate([
                'id_approval_invoice' => 'required|exists:approval_invoice,id_approval_invoice',
                'remarks' => 'nullable|string|max:255',
                'id_invoice' => 'required|exists:invoice,id_invoice',
                'status' => 'required|in:approved,rejected',
            ]);
            $approval = DB::table('approval_invoice')
                ->where('id_approval_invoice', $request->input('id_approval_invoice'))
                ->where('id_invoice', $request->input('id_invoice'))
                ->where('status', 'pending')
                ->first();
            if (!$approval) {
                throw new Exception('No pending approval found for this invoice');
            } else {
                if ($approval->approval_position == Auth::user()->id_position && $approval->approval_division == Auth::user()->id_division) {
                    $update = DB::table('approval_invoice')
                        ->where('id_approval_invoice', $request->input('id_approval_invoice'))
                        ->update([
                            'status' => $request->input('status'),
                            'remarks' => $request->input('remarks'),
                            'approved_at' => now(),
                            'approved_by' => Auth::id(),
                            'updated_at' => now(),
                            'updated_by' => Auth::id(),
                        ]);
                    if (!$update) {
                        throw new Exception('Failed to update approval status');
                    } else {
                        if ($request->status == 'rejected') {
                            $updateInvoice = DB::table('invoice')
                                ->where('id_invoice', $request->id_invoice)
                                ->update([
                                    'status_approval' => 'invoice_rejected',
                                ]);
                            if (!$updateInvoice) {
                                throw new Exception('Failed to update invoice status');
                            }
                        } else {
                            $pendingApproval = DB::table('approval_invoice')
                                ->where('id_invoice', $request->id_invoice)
                                ->where('status', 'pending')
                                ->orderBy('step_no', 'ASC')
                                ->first();
                            if (!$pendingApproval) {
                                $updateInvoice = DB::table('invoice')
                                    ->where('id_invoice', $request->id_invoice)
                                    ->update([
                                        'status_approval' => 'invoice_approved',
                                    ]);
                                if (!$updateInvoice) {
                                    throw new Exception('Failed to update invoice status');
                                }
                            }
                        }
                    }
                } else{
                    throw new Exception('You are not authorized to approve this invoice');
                }
            }
            DB::commit();
            return ResponseHelper::success('Invoice approval updated successfully',null,200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
