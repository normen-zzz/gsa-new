<?php

namespace App\Http\Controllers\flow\jobsheet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Helpers\ResponseHelper;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Storage;


date_default_timezone_set('Asia/Jakarta');
class JobsheetController extends Controller
{
    public function createJobsheet(Request $request)
    {
        DB::beginTransaction();

        try {
            // Logic to create a jobsheet
            // Validate the request data
            $request->validate([
                'id_salesorder' => 'required|integer|exists:salesorder,id_salesorder',
                'remarks' => 'nullable|string|max:255',
                'attachments' => 'required|array',
                'attachments.*.image' => 'nullable|string',
                'cost' => 'required|array',
                'cost.*.id_typecost' => 'required|integer|exists:typecost,id_typecost',
                'cost.*.cost_value' => 'required|numeric|min:0',
                'cost.*.charge_by' => 'nullable|in:chargeable_weight,gross_weight,awb',
                'cost.*.description' => 'nullable|string|max:255',
                'cost.*.id_vendor' => 'required|integer|exists:vendors,id_vendor'
            ]);

            $id_shippinginstruction = DB::table('salesorder')
                ->where('id_salesorder', $request->id_salesorder)
                ->value('id_shippinginstruction');
            $id_job = DB::table('job')
                ->where('id_shippinginstruction', $id_shippinginstruction)
                ->value('id_job');
            $awb = DB::table('awb')->where('id_job', $id_job)->first();

            $salesorder = DB::table('salesorder')->where('id_salesorder', $request->id_salesorder)->first();
            $id_job = $awb->id_job ?? null;
            $id_shippinginstruction = DB::table('job')->where('id_job', $id_job)->value('id_shippinginstruction');
            $no_jobsheet = $salesorder->no_salesorder;
            $dataJobsheet = [
                'id_shippinginstruction' => $id_shippinginstruction,
                'id_job' => $id_job,
                'id_awb' => $awb->id_awb,
                'id_salesorder' => $request->id_salesorder,
                'no_jobsheet' => $no_jobsheet,
                'remarks' => $request->remarks,
                'created_by' => Auth::id(),

            ];
            $insertJobsheet = DB::table('jobsheet')->insertGetId($dataJobsheet);
            if ($insertJobsheet) {
                if (isset($request->attachments) && is_array($request->attachments)) {
                    $no = 1;
                    foreach ($request->attachments as $attachment) {
                        $file_name = time() . '/' . $no . '_' . $insertJobsheet;
                        $no++;
                        // Decode the base64 image
                        $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $attachment['image']));
                        $extension = explode('/', mime_content_type($attachment['image']))[1];

                        // Save file to public storage
                        $path = 'jobsheets/' . $file_name . '.' . $extension;
                        Storage::disk('public')->put($path, $image);

                        // Ensure storage link exists
                        if (!file_exists(public_path('storage'))) {
                            throw new Exception('Storage link not found. Please run "php artisan storage:link" command');
                        }

                        // Generate public URL that can be accessed directly
                        $url = url('storage/' . $path);

                        $attachments = [
                            'id_jobsheet' => $insertJobsheet, // Will be set after jobsheet creation
                            'file_name' => $file_name,
                            'url' => $url,
                            'public_id' => $path,
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ];
                        DB::table('attachments_jobsheet')->insert($attachments);
                    }
                } else {
                    throw new Exception('Invalid attachments format');
                }
                $cost = $request->cost;
                foreach ($cost as $item) {
                    $dataCost = [
                        'id_jobsheet' => $insertJobsheet,
                        'id_typecost' => $item['id_typecost'],
                        'cost_value' => $item['cost_value'],
                        'charge_by' => $item['charge_by'],
                        'description' => $item['description'] ?? null,
                        'id_vendor' => $item['id_vendor'],
                        'created_by' => Auth::id(),
                    ];
                    DB::table('cost_jobsheet')->insert($dataCost);
                }
            } else {
                throw new Exception('Failed to create jobsheet');
            }
            $id_position = Auth::user()->id_position;
            $id_division = Auth::user()->id_division;
            if (!$id_position || !$id_division) {
                throw new Exception('Invalid user position or division');
            }
            $flow_approval = DB::table('flowapproval_jobsheet')
                ->where(['request_position' => $id_position, 'request_division' => $id_division])
                ->first();
            if (!$flow_approval) {
                throw new Exception('No flow approval found for the user position and division');
            } else {
                $detailApproval = DB::table('detailflowapproval_jobsheet')
                    ->where('id_flowapproval_jobsheet', $flow_approval->id_flowapproval_jobsheet)
                    ->get();
                foreach ($detailApproval as $approval) {
                    $approval = [
                        'id_jobsheet' => $insertJobsheet,
                        'approval_position' => $approval->approval_position,
                        'approval_division' => $approval->approval_division,
                        'step_no' => $approval->step_no,
                        'status' => 'pending',
                        'created_by' => Auth::id(),
                    ];
                    DB::table('approval_jobsheet')->insert($approval);
                }
            }
            DB::commit();
            return ResponseHelper::success('Jobsheet created successfully', null, 201);
        } catch (Exception $e) {
            DB::rollBack();
            // Remove image if exists
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            return ResponseHelper::error($e);
        }
    }

    public function getJobsheet(Request $request)
    {
        $limit = $request->input('limit', 10);
        $searchKey = $request->input('searchKey', '');

        $select = [
            'a.id_jobsheet',
            'a.no_jobsheet',
            'a.id_salesorder',
            'a.id_shippinginstruction',
            'a.id_job',
            'a.id_awb',
            'c.agent',
            'cu.name_customer AS agent_name',
            'c.consignee',
            'a.remarks',
            'a.created_at',
            'a.created_by',
            'b.name AS created_by_name',
            'a.deleted_at',
            'a.deleted_by',
            'd.name AS deleted_by_name',
            'a.status_jobsheet',
            'a.status_approval',
        ];


        $jobsheets = DB::table('jobsheet AS a')
            ->select($select)
            ->leftJoin('users AS b', 'a.created_by', '=', 'b.id_user')
            ->leftJoin('users AS d', 'a.deleted_by', '=', 'd.id_user')
            ->leftJoin('awb AS c', 'a.id_awb', '=', 'c.id_awb')
            ->leftJoin('customers AS cu', 'c.agent', '=', 'cu.id_customer')
            ->leftJoin('salesorder AS so', 'a.id_salesorder', '=', 'so.id_salesorder')
            ->when($searchKey, function ($query, $searchKey) {
                $query->where('cu.name_customer', 'like', "%{$searchKey}%");
            })
            ->paginate($limit);

        $jobsheets->getCollection()->transform(function ($item) {

            $selectAttachments = [
                'attachments_jobsheet.id_attachment_jobsheet',
                'attachments_jobsheet.id_jobsheet',
                'attachments_jobsheet.file_name',
                'attachments_jobsheet.url',
                'attachments_jobsheet.public_id',
                'attachments_jobsheet.created_by',
                'created_by.name AS created_by_name',
                'attachments_jobsheet.created_at',
                'attachments_jobsheet.deleted_at',
                'deleted_by.name AS deleted_by_name'

            ];

            $attachments = DB::table('attachments_jobsheet')
                ->leftJoin('users AS created_by', 'attachments_jobsheet.created_by', '=', 'created_by.id_user')
                ->leftJoin('users AS deleted_by', 'attachments_jobsheet.deleted_by', '=', 'deleted_by.id_user')
                ->where('id_jobsheet', $item->id_jobsheet)
                ->select($selectAttachments)
                ->get();



            $selectCost = [
                'cost_jobsheet.id_cost_jobsheet',
                'cost_jobsheet.id_jobsheet',
                'cost_jobsheet.id_typecost',
                'ts.name AS typecost_name',
                'cost_jobsheet.cost_value',
                'cost_jobsheet.charge_by',
                'cost_jobsheet.description',
                'cost_jobsheet.id_vendor',
                'v.name_vendor AS vendor_name',
                'cost_jobsheet.created_by',
                'created_by.name AS created_by_name',
                'cost_jobsheet.created_at'
            ];

            $cost = DB::table('cost_jobsheet')
                ->where('id_jobsheet', $item->id_jobsheet)
                ->leftJoin('typecost AS ts', 'cost_jobsheet.id_typecost', '=', 'ts.id_typecost')
                ->leftJoin('users AS created_by', 'cost_jobsheet.created_by', '=', 'created_by.id_user')
                ->leftJoin('vendors AS v', 'cost_jobsheet.id_vendor', '=', 'v.id_vendor')
                ->select($selectCost)
                ->get();

            $selectApproval = [
                'approval_jobsheet.id_approval_jobsheet',
                'approval_jobsheet.id_jobsheet',
                'approval_jobsheet.approval_position',
                'approval_position.name AS approval_position_name',
                'approval_jobsheet.approval_division',
                'approval_division.name AS approval_division_name',
                'approval_jobsheet.step_no',

                'approval_jobsheet.created_by',
                'created_by.name AS created_by_name',
                'approval_jobsheet.approved_by',
                'approved_by.name AS approved_by_name',
                'approval_jobsheet.status',

            ];

            $approval_jobsheet = DB::table('approval_jobsheet')
                ->select($selectApproval)
                ->leftJoin('users AS approval_position', 'approval_jobsheet.approval_position', '=', 'approval_position.id_user')
                ->leftJoin('users AS approval_division', 'approval_jobsheet.approval_division', '=', 'approval_division.id_user')
                ->leftJoin('users AS approved_by', 'approval_jobsheet.approved_by', '=', 'approved_by.id_user')
                ->leftJoin('users AS created_by', 'approval_jobsheet.created_by', '=', 'created_by.id_user')
                ->where('id_jobsheet', $item->id_jobsheet)
                ->get();

            $selectAwb = [
                'awb.id_awb',
                'awb.id_job',
                'awb.awb',
                'awb.agent',
                'agent.name_customer as agent_name',
                'awb.data_agent as id_data_agent',
                'data_agent.pic as data_agent_pic',
                'data_agent.email as data_agent_email',
                'data_agent.phone as data_agent_phone',
                'data_agent.tax_id as data_agent_tax_id',
                'data_agent.address as data_agent_address',
                'awb.consignee',
                'awb.airline',
                'airline.name as airline_name',
                'awb.etd',
                'awb.eta',
                'awb.pol',
                'pol.name_airport as pol_name',
                'pol.code_airport as pol_code',
                'awb.pod',
                'pod.name_airport as pod_name',
                'pod.code_airport as pod_code',
                'awb.commodity',
                'awb.gross_weight',
                'awb.chargeable_weight',
                'awb.pieces',
                'awb.special_instructions',
                'awb.created_by',
                'created_by.name as created_by_name',
                'awb.updated_by',
                'updated_by.name as updated_by_name',
                'awb.created_at',
                'awb.updated_at',
                'awb.status'
            ];
            $awb = DB::table('awb')
                ->select($selectAwb)
                ->leftJoin('customers AS agent', 'awb.agent', '=', 'agent.id_customer')
                ->leftJoin('data_customer AS data_agent', 'awb.data_agent', '=', 'data_agent.id_datacustomer')
                ->leftJoin('airports AS pol', 'awb.pol', '=', 'pol.id_airport')
                ->leftJoin('airports AS pod', 'awb.pod', '=', 'pod.id_airport')
                ->leftJoin('users AS created_by', 'awb.created_by', '=', 'created_by.id_user')
                ->leftJoin('users AS updated_by', 'awb.updated_by', '=', 'updated_by.id_user')
                ->leftJoin('airlines AS airline', 'awb.airline', '=', 'airline.id_airline')
                ->where('id_awb', $item->id_awb)
                ->first();

            $pendingApproval = DB::table('approval_jobsheet')
                ->where('id_jobsheet', $item->id_jobsheet)
                ->where('status', 'pending')
                ->orderBy('step_no', 'ASC')
                ->first();

            $position = Auth::user()->id_position;
            $division = Auth::user()->id_division;
            if ($pendingApproval && $pendingApproval->approval_position == $position && $pendingApproval->approval_division == $division) {
                $item->is_approver = true;
            } else {
                $item->is_approver = false;
            }


            $item->data_awb = $awb;
            $item->attachments_jobsheet = $attachments;
            $item->cost_jobsheet = $cost;
            $item->approval_jobsheet = $approval_jobsheet;
            return $item;
        });
        return ResponseHelper::success('Jobsheets retrieved successfully', $jobsheets);
    }

    public function getUninvoicedJobsheet(Request $request)
    {

        $id_agent = $request->input('id_agent');
        if (!$id_agent) {
            return ResponseHelper::success('Agent ID is required', null, 400);
        } else {

            $select = [
                'a.id_jobsheet',
                'a.id_salesorder',
                'a.id_shippinginstruction',
                'a.id_job',
                'a.id_awb',
                'c.agent',
                'cu.name_customer AS agent_name',
                'c.consignee',
                'c.pol',
                'pol.name_airport AS pol_name',
                'pol.code_airport AS pol_code',
                'c.pod',
                'pod.name_airport AS pod_name',
                'pod.code_airport AS pod_code',
                'a.remarks',
                'a.created_at',
                'a.created_by',
                'b.name AS created_by_name',
                'a.deleted_at',
                'a.deleted_by',
                'd.name AS deleted_by_name',
                'a.status_jobsheet',
                'a.status_approval',
            ];


            $jobsheets = DB::table('jobsheet AS a')
                ->select($select)
                ->leftJoin('users AS b', 'a.created_by', '=', 'b.id_user')
                ->leftJoin('users AS d', 'a.deleted_by', '=', 'd.id_user')
                ->leftJoin('awb AS c', 'a.id_awb', '=', 'c.id_awb')
                ->leftJoin('customers AS cu', 'c.agent', '=', 'cu.id_customer')
                ->leftJoin('salesorder AS so', 'a.id_salesorder', '=', 'so.id_salesorder')
                ->leftJoin('airports AS pol', 'c.pol', '=', 'pol.id_airport')
                ->leftJoin('airports AS pod', 'c.pod', '=', 'pod.id_airport')
                ->where('c.agent', $id_agent)
                ->where('a.status_jobsheet', 'js_received')
                ->get();

            $jobsheets->transform(function ($item) {
                $selectApproval = [
                    'approval_jobsheet.id_approval_jobsheet',
                    'approval_jobsheet.id_jobsheet',
                    'approval_jobsheet.approval_position',
                    'approval_position.name AS approval_position_name',
                    'approval_jobsheet.approval_division',
                    'approval_division.name AS approval_division_name',
                    'approval_jobsheet.step_no',

                    'approval_jobsheet.created_by',
                    'created_by.name AS created_by_name',
                    'approval_jobsheet.approved_by',
                    'approved_by.name AS approved_by_name',
                    'approval_jobsheet.status',

                ];

                $approval_jobsheet = DB::table('approval_jobsheet')
                    ->select($selectApproval)
                    ->leftJoin('users AS approval_position', 'approval_jobsheet.approval_position', '=', 'approval_position.id_user')
                    ->leftJoin('users AS approval_division', 'approval_jobsheet.approval_division', '=', 'approval_division.id_user')
                    ->leftJoin('users AS approved_by', 'approval_jobsheet.approved_by', '=', 'approved_by.id_user')
                    ->leftJoin('users AS created_by', 'approval_jobsheet.created_by', '=', 'created_by.id_user')
                    ->where('id_jobsheet', $item->id_jobsheet)
                    ->get();
                // deteksi apakah masih ada status approval yang pending 
                $item->is_approvable = $approval_jobsheet->contains('status', 'pending') ? false : true;

                $selectAttachments = [
                    'attachments_jobsheet.id_attachment_jobsheet',
                    'attachments_jobsheet.id_jobsheet',
                    'attachments_jobsheet.file_name',
                    'attachments_jobsheet.url',
                    'attachments_jobsheet.public_id',
                    'attachments_jobsheet.created_by',
                    'created_by.name AS created_by_name',
                    'attachments_jobsheet.created_at',
                    'attachments_jobsheet.deleted_at',
                    'deleted_by.name AS deleted_by_name'

                ];

                $attachments = DB::table('attachments_jobsheet')
                    ->leftJoin('users AS created_by', 'attachments_jobsheet.created_by', '=', 'created_by.id_user')
                    ->leftJoin('users AS deleted_by', 'attachments_jobsheet.deleted_by', '=', 'deleted_by.id_user')
                    ->where('id_jobsheet', $item->id_jobsheet)
                    ->select($selectAttachments)
                    ->get();



                $selectCost = [
                    'cost_jobsheet.id_cost_jobsheet',
                    'cost_jobsheet.id_jobsheet',
                    'cost_jobsheet.id_typecost',
                    'ts.name AS typecost_name',
                    'cost_jobsheet.cost_value',
                    'cost_jobsheet.charge_by',
                    'cost_jobsheet.description',
                    'cost_jobsheet.id_vendor',
                    'v.name_vendor AS vendor_name',
                    'cost_jobsheet.created_by',
                    'created_by.name AS created_by_name',
                    'cost_jobsheet.created_at'
                ];

                $cost = DB::table('cost_jobsheet')
                    ->where('id_jobsheet', $item->id_jobsheet)
                    ->leftJoin('typecost AS ts', 'cost_jobsheet.id_typecost', '=', 'ts.id_typecost')
                    ->leftJoin('users AS created_by', 'cost_jobsheet.created_by', '=', 'created_by.id_user')
                    ->leftJoin('vendors AS v', 'cost_jobsheet.id_vendor', '=', 'v.id_vendor')
                    ->select($selectCost)
                    ->get();



                $item->attachments_jobsheet = $attachments;
                $item->cost_jobsheet = $cost;
                $item->approval_jobsheet = $approval_jobsheet;
                return $item;
            });

            return ResponseHelper::success('Jobsheets retrieved successfully', $jobsheets);
        }
    }

    public function getJobsheetById(Request $request)
    {
        $id = $request->input('id_jobsheet');
        $select = [
            'a.id_jobsheet',
            'a.id_salesorder',
            'a.id_shippinginstruction',
            'a.id_job',
            'a.id_awb',
            'c.agent',
            'cu.name_customer AS agent_name',
            'c.consignee',
            'a.remarks',
            'a.created_at',
            'a.created_by',
            'b.name AS created_by_name',
            'a.deleted_at',
            'a.deleted_by',
            'd.name AS deleted_by_name',
            'a.status_jobsheet',
            'a.status_approval',
        ];


        $jobsheet = DB::table('jobsheet AS a')
            ->select($select)
            ->leftJoin('users AS b', 'a.created_by', '=', 'b.id_user')
            ->leftJoin('users AS d', 'a.deleted_by', '=', 'd.id_user')
            ->leftJoin('awb AS c', 'a.id_awb', '=', 'c.id_awb')
            ->leftJoin('customers AS cu', 'c.agent', '=', 'cu.id_customer')
            ->leftJoin('salesorder AS so', 'a.id_salesorder', '=', 'so.id_salesorder')
            ->where('a.id_jobsheet', $id)
            ->first();



        $selectAttachments = [
            'attachments_jobsheet.id_attachment_jobsheet',
            'attachments_jobsheet.id_jobsheet',
            'attachments_jobsheet.file_name',
            'attachments_jobsheet.url',
            'attachments_jobsheet.public_id',
            'attachments_jobsheet.created_by',
            'created_by.name AS created_by_name',
            'attachments_jobsheet.created_at',
            'attachments_jobsheet.deleted_at',
            'deleted_by.name AS deleted_by_name'

        ];

        $attachments = DB::table('attachments_jobsheet')
            ->leftJoin('users AS created_by', 'attachments_jobsheet.created_by', '=', 'created_by.id_user')
            ->leftJoin('users AS deleted_by', 'attachments_jobsheet.deleted_by', '=', 'deleted_by.id_user')
            ->where('id_jobsheet', $jobsheet->id_jobsheet)
            ->select($selectAttachments)
            ->get();



        $selectCost = [
            'cost_jobsheet.id_cost_jobsheet',
            'cost_jobsheet.id_jobsheet',
            'cost_jobsheet.id_typecost',
            'ts.name AS typecost_name',
            'ts.initials AS typecost_initials',
            'cost_jobsheet.cost_value',
            'cost_jobsheet.charge_by',
            'cost_jobsheet.description',
            'cost_jobsheet.id_vendor',
            'v.name_vendor AS vendor_name',
            'cost_jobsheet.created_by',
            'created_by.name AS created_by_name',
            'cost_jobsheet.created_at'
        ];

        $cost = DB::table('cost_jobsheet')
            ->where('id_jobsheet', $jobsheet->id_jobsheet)
            ->join('typecost AS ts', 'cost_jobsheet.id_typecost', '=', 'ts.id_typecost')
            ->leftJoin('users AS created_by', 'cost_jobsheet.created_by', '=', 'created_by.id_user')
            ->leftJoin('vendors AS v', 'cost_jobsheet.id_vendor', '=', 'v.id_vendor')
            ->select($selectCost)
            ->get();

        $selectApproval = [
            'approval_jobsheet.id_approval_jobsheet',
            'approval_jobsheet.id_jobsheet',
            'approval_jobsheet.approval_position',
            'approval_position.name AS approval_position_name',
            'approval_jobsheet.approval_division',
            'approval_division.name AS approval_division_name',
            'approval_jobsheet.step_no',

            'approval_jobsheet.created_by',
            'created_by.name AS created_by_name',
            'approval_jobsheet.approved_by',
            'approved_by.name AS approved_by_name',
            'approval_jobsheet.status',

        ];

        $approval_jobsheet = DB::table('approval_jobsheet')
            ->select($selectApproval)
            ->leftJoin('users AS approval_position', 'approval_jobsheet.approval_position', '=', 'approval_position.id_user')
            ->leftJoin('users AS approval_division', 'approval_jobsheet.approval_division', '=', 'approval_division.id_user')
            ->leftJoin('users AS approved_by', 'approval_jobsheet.approved_by', '=', 'approved_by.id_user')
            ->leftJoin('users AS created_by', 'approval_jobsheet.created_by', '=', 'created_by.id_user')
            ->where('id_jobsheet', $jobsheet->id_jobsheet)
            ->get();

        $selectAwb = [
            'awb.id_awb',
            'awb.id_job',
            'awb.awb',
            'awb.agent',
            'agent.name_customer as agent_name',
            'awb.data_agent as id_data_agent',
            'data_agent.pic as data_agent_pic',
            'data_agent.email as data_agent_email',
            'data_agent.phone as data_agent_phone',
            'data_agent.tax_id as data_agent_tax_id',
            'data_agent.address as data_agent_address',
            'awb.consignee',
            'awb.airline',
            'airline.name as airline_name',
            'awb.etd',
            'awb.eta',
            'awb.pol',
            'pol.name_airport as pol_name',
            'pol.code_airport as pol_code',
            'awb.pod',
            'pod.name_airport as pod_name',
            'pod.code_airport as pod_code',
            'awb.commodity',
            'awb.gross_weight',
            'awb.chargeable_weight',
            'awb.pieces',
            'awb.special_instructions',
            'awb.created_by',
            'created_by.name as created_by_name',
            'awb.updated_by',
            'updated_by.name as updated_by_name',
            'awb.created_at',
            'awb.updated_at',
            'awb.status'
        ];
        $awb = DB::table('awb')
            ->select($selectAwb)
            ->leftJoin('customers AS agent', 'awb.agent', '=', 'agent.id_customer')
            ->leftJoin('data_customer AS data_agent', 'awb.data_agent', '=', 'data_agent.id_datacustomer')
            ->leftJoin('airports AS pol', 'awb.pol', '=', 'pol.id_airport')
            ->leftJoin('airports AS pod', 'awb.pod', '=', 'pod.id_airport')
            ->leftJoin('users AS created_by', 'awb.created_by', '=', 'created_by.id_user')
            ->leftJoin('users AS updated_by', 'awb.updated_by', '=', 'updated_by.id_user')
            ->leftJoin('airlines AS airline', 'awb.airline', '=', 'airline.id_airline')
            ->where('id_awb', $jobsheet->id_awb)
            ->first();

        $pendingApproval = DB::table('approval_jobsheet')
            ->where('id_jobsheet', $jobsheet->id_jobsheet)
            ->where('status', 'pending')
            ->orderBy('step_no', 'ASC')
            ->first();

        $position = Auth::user()->id_position;
        $division = Auth::user()->id_division;
        if ($pendingApproval && $pendingApproval->approval_position == $position && $pendingApproval->approval_division == $division) {
            $jobsheet->is_approver = true;
        } else {
            $jobsheet->is_approver = false;
        }


        $jobsheet->data_awb = $awb;
        $jobsheet->attachments_jobsheet = $attachments;
        $jobsheet->cost_jobsheet = $cost;
        $jobsheet->approval_jobsheet = $approval_jobsheet;

        return ResponseHelper::success('Jobsheet retrieved successfully', $jobsheet);
    }

    public function deleteAttachmentJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_attachment_jobsheet' => 'required|integer|exists:attachments_jobsheet,id_attachment_jobsheet',
            ]);

            $id_attachment_jobsheet = $request->id_attachment_jobsheet;

            $attachment = DB::table('attachments_jobsheet')->where('id_attachment_jobsheet', $id_attachment_jobsheet)->first();

            $delete = DB::table('attachments_jobsheet')->where('id_attachment_jobsheet', $id_attachment_jobsheet)->delete();
            if ($delete) {
                // Delete from Cloudinary
                if ($attachment && isset($attachment->public_id)) {
                    if (Storage::disk('public')->exists($attachment->public_id)) {
                        Storage::disk('public')->delete($attachment->public_id);
                    }
                }
            } else {
                throw new Exception('Failed to delete attachment');
            }
            DB::commit();
            return ResponseHelper::success('Attachment deleted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function updateJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_jobsheet' => 'required|integer|exists:jobsheet,id_jobsheet',
                'cost' => 'nullable|array',
                'cost.*.id_cost_jobsheet' => 'nullable|integer|exists:cost_jobsheet,id_cost_jobsheet',
                'cost.*.id_typecost' => 'required|integer|exists:typecost,id_typecost',
                'cost.*.cost_value' => 'required|numeric',
                'cost.*.charge_by' => 'required|string',
                'cost.*.description' => 'nullable|string',
                'cost.*.id_vendor' => 'required|integer|exists:vendors,id_vendor',
                'attachments' => 'nullable|array',
                'attachments.*.image' => 'required|string',
            ]);

            $changesCost = [];
            $changesAttachments = [];

            if ($request->has('cost')) {
                foreach ($request->cost as $cost) {
                    if ($cost['id_cost_jobsheet'] != null) {
                        $existingCost = DB::table('cost_jobsheet')->where('id_cost_jobsheet', $cost['id_cost_jobsheet'])->first();
                        $update = DB::table('cost_jobsheet')
                            ->where('id_cost_jobsheet', $cost['id_cost_jobsheet'])
                            ->update([
                                'id_typecost' => $cost['id_typecost'],
                                'cost_value' => $cost['cost_value'],
                                'charge_by' => $cost['charge_by'],
                                'description' => $cost['description'] ?? null,
                                'id_vendor' => $cost['id_vendor'],
                                'updated_by' => Auth::id(),
                            ]);
                        if (!$update) {
                            throw new Exception('Failed to update cost');
                        } else {
                            $changesCost[] = [
                                'id_cost_jobsheet' => $cost['id_cost_jobsheet'],
                                'old' => $existingCost,
                                'new' => [
                                    'id_typecost' => $cost['id_typecost'],
                                    'cost_value' => $cost['cost_value'],
                                    'charge_by' => $cost['charge_by'],
                                    'description' => $cost['description'] ?? null,
                                    'id_vendor' => $cost['id_vendor'],
                                    'updated_by' => Auth::id(),
                                ]
                            ];
                        }
                    } else {
                        $insert = DB::table('cost_jobsheet')->insert([
                            'id_jobsheet' => $request->id_jobsheet,
                            'id_typecost' => $cost['id_typecost'],
                            'cost_value' => $cost['cost_value'],
                            'charge_by' => $cost['charge_by'],
                            'description' => $cost['description'] ?? null,
                            'id_vendor' => $cost['id_vendor'],
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ]);
                        if (!$insert) {
                            throw new Exception('Failed to insert cost');
                        } else {
                            $changesCost[] = [
                                'id_cost_jobsheet' => $cost['id_cost_jobsheet'],
                                'old' => null,
                                'new' => [
                                    'id_typecost' => $cost['id_typecost'],
                                    'cost_value' => $cost['cost_value'],
                                    'charge_by' => $cost['charge_by'],
                                    'description' => $cost['description'] ?? null,
                                    'id_vendor' => $cost['id_vendor'],
                                    'created_by' => Auth::id(),
                                    'created_at' => now(),
                                ]
                            ];
                        }
                    }
                }
            }
            if ($request->has('attachments') && is_array($request->attachments) && count($request->attachments) > 0) {
                $no = 1;

                foreach ($request->attachments as $attachment) {
                    $file_name = time() . '/' . $no . '_' . $request->id_jobsheet;
                    $no++;
                    // Decode the base64 image
                    $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $attachment['image']));

                    // Save file to public storage
                    $path = 'salesorders/' . $file_name;
                    Storage::disk('public')->put($path, $image);

                    // Ensure storage link exists
                    if (!file_exists(public_path('storage'))) {
                        throw new Exception('Storage link not found. Please run "php artisan storage:link" command');
                    }

                    // Generate public URL that can be accessed directly
                    $url = url('storage/' . $path);

                    $dataAttachment = [
                        'id_jobsheet' => $request->id_jobsheet,
                        'file_name' => $file_name,
                        'url' => $url,
                        'public_id' => $path,
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ];
                    $publicId = DB::table('attachments_jobsheet')->insertGetId($dataAttachment);
                    if (!$publicId) {
                        throw new Exception('Failed to insert attachment');
                    }
                }
                $changesAttachments[] = [
                    'type' => 'Add Attachment',
                    'id_attachment_jobsheet' => $publicId,
                    'data' => $dataAttachment
                ];
            }

            $log = [
                'id_jobsheet' => $request->id_jobsheet,
                'action' => json_encode([
                    'type' => 'update',
                    'data' => [
                        'cost' => $changesCost,
                        'attachments' => $changesAttachments
                    ]
                ]),
                'created_by' => Auth::id(),
                'created_at' => now(),
            ];
            DB::table('log_jobsheet')->insert($log);

            DB::commit();
            return ResponseHelper::success('Jobsheet updated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            // Remove image if exists
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            return ResponseHelper::error($e);
        }
    }

    public function deleteJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_jobsheet' => 'required|integer|exists:jobsheet,id_jobsheet',
            ], [
                'id_jobsheet.required' => 'ID Jobsheet is required',
                'id_jobsheet.integer' => 'ID Jobsheet must be an integer',
                'id_jobsheet.exists' => 'ID Jobsheet does not exist in jobsheet',
            ]);

            $update = DB::table('jobsheet')->where('id_jobsheet', $request->id_jobsheet)
                ->update([
                    'deleted_at' => now(),
                    'deleted_by' => Auth::id(),
                    'status' => 'js_deleted'
                ]);
            if (!$update) {
                throw new Exception('Failed to delete jobsheet');
            } else {
                $log = [
                    'id_jobsheet' => $request->id_jobsheet,
                    'action' => [
                        'type' => 'delete',
                        'data' => [
                            'deleted_by' => Auth::id(),
                            'deleted_at' => now()
                        ]
                    ]
                ];
                DB::table('log_jobsheet')->insert($log);
            }

            DB::commit();
            return ResponseHelper::success('Jobsheet deleted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function activateJobsheet(Request $request)
    {

        DB::beginTransaction();
        try {
            $request->validate([
                'id_jobsheet' => 'required|integer|exists:jobsheet,id_jobsheet',
            ]);



            $update = DB::table('jobsheet')
                ->where('id_jobsheet', $request->id_jobsheet)
                ->update([
                    'deleted_by' => null,
                    'deleted_at' => null,
                    'status' => 'js_created_by_cs'
                ]);
            if ($update) {
                $log = [
                    'id_jobsheet' => $request->id_jobsheet,
                    'action' => [
                        'type' => 'activate',
                        'data' => [
                            'activated_by' => Auth::id(),
                            'activated_at' => now()
                        ]
                    ]
                ];
                DB::table('log_jobsheet')->insert($log);
            }
            DB::commit();
            return ResponseHelper::success('Jobsheet activated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function approveJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_approval_jobsheet' => 'required|integer|exists:approval_jobsheet,id_approval_jobsheet',
                'remarks' => 'nullable|string|max:255',
                'id_jobsheet' => 'required|integer|exists:jobsheet,id_jobsheet',
                'status' => 'required|in:approved,rejected'
            ]);

            $approval = DB::table('approval_jobsheet')
                ->where('id_approval_jobsheet', $request->id_approval_jobsheet)
                ->where('id_jobsheet', $request->id_jobsheet)
                ->first();

            if ($approval) {
                if ($approval->approval_position == Auth::user()->id_position && $approval->approval_division == Auth::user()->id_division) {
                    $update = DB::table('approval_jobsheet')
                        ->where('id_approval_jobsheet', $request->id_approval_jobsheet)
                        ->where('approval_position', Auth::user()->id_position)
                        ->where('approval_division', Auth::user()->id_division)
                        ->update([
                            'status' => $request->status,
                            'remarks' => $request->remarks ?? null,
                            'approved_by' => Auth::id(),
                            'updated_at' => now(),
                        ]);

                    if (!$update) {
                        throw new Exception('Failed to update approval status because');
                    } else {
                        if ($request->status == 'rejected') {
                            $updateJobsheet = DB::table('jobsheet')
                                ->where('id_jobsheet', $request->id_jobsheet)
                                ->update([
                                    'status_approval' => 'js_rejected',
                                ]);
                            if (!$updateJobsheet) {
                                throw new Exception('Failed to update jobsheet status');
                            }
                        } else {
                            $pendingApproval = DB::table('approval_jobsheet')
                                ->where('id_jobsheet', $request->id_jobsheet)
                                ->where('status', 'pending')
                                ->orderBy('step_no', 'ASC')
                                ->first();
                            if (!$pendingApproval) {
                                $updateJobsheet = DB::table('jobsheet')
                                    ->where('id_jobsheet', $request->id_jobsheet)
                                    ->update([
                                        'status_approval' => 'js_approved',
                                    ]);
                                if (!$updateJobsheet) {
                                    throw new Exception('Failed to update jobsheet status');
                                }
                            }
                        }
                        $log = [
                            'id_jobsheet' => $request->id_jobsheet,
                            'action' => [
                                'type' => 'approve',
                                'data' => [
                                    'id_approval_jobsheet' => $request->id_approval_jobsheet,
                                    'approved_by' => Auth::id(),
                                    'approved_at' => now()
                                ]
                            ]
                        ];
                        DB::table('log_jobsheet')->insert($log);
                    }
                } else {
                    throw new Exception('You are not authorized to update this approval status');
                }
            }
            DB::commit();
            return ResponseHelper::success('Jobsheet approved successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function receiveJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_jobsheet' => 'required|integer|exists:jobsheet,id_jobsheet',
            ]);
            $update = DB::table('jobsheet')
                ->where('id_jobsheet', $request->id_jobsheet)
                ->update([
                    'status_jobsheet' => 'js_received',
                    'received_at' => now(),
                    'received_by' => Auth::id(),
                ]);
            return ResponseHelper::success('Jobsheet received successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function resubmitJobsheet(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_jobsheet' => 'required|integer|exists:jobsheet,id_jobsheet',
                'remarks' => 'nullable|string|max:255',
                'attachments' => 'required|array',
                'attachments.*.image' => 'nullable|string',
                'cost' => 'required|array',
                'cost.*.id_typecost' => 'required|integer|exists:typecost,id_typecost',
                'cost.*.cost_value' => 'required|numeric|min:0',
                'cost.*.charge_by' => 'nullable|in:chargeable_weight,gross_weight,awb',
                'cost.*.description' => 'nullable|string|max:255',
                'cost.*.id_vendor' => 'required|integer|exists:vendors,id_vendor'
            ]);
            $jobsheet = DB::table('jobsheet')->where('id_jobsheet', $request->id_jobsheet)->first();
            $attachments = DB::table('attachments_jobsheet')->where('id_jobsheet', $request->id_jobsheet)->get();
            $costs = DB::table('cost_jobsheet')->where('id_jobsheet', $request->id_jobsheet)->get();
            if (!$jobsheet) {
                throw new Exception('Jobsheet not found');
            }
            if (count($attachments) > 0) {
                foreach ($attachments as $attachment) {
                    if (Storage::disk('public')->exists($attachment->public_id)) {
                        Storage::disk('public')->delete($attachment->public_id);
                    }
                }
            }
            $deleteAttachments = DB::table('attachments_jobsheet')->where('id_jobsheet', $request->id_jobsheet)->delete();
            if ($deleteAttachments === false) {
                throw new Exception('Failed to delete existing attachments');
            }
            $deleteCosts = DB::table('cost_jobsheet')->where('id_jobsheet', $request->id_jobsheet)->delete();
            if ($deleteCosts === false) {
                throw new Exception('Failed to delete existing costs');
            }
            if ($request->has('attachments') && is_array($request->attachments) && count($request->attachments) > 0) {
                $no = 1;
                foreach ($request->attachments as $attachment) {
                    if (isset($attachment['image']) && $attachment['image'] != null) {
                        // Generate a unique filename with timestamp
                        $file_name = time() . '/' . $no . '_' . $request->id_jobsheet;
                        $no++;
                        // Decode the base64 image
                        $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $attachment['image']));
                        $extension = explode('/', mime_content_type($attachment['image']))[1];

                        // Save file to public storage
                        $path = 'jobsheets/' . $file_name . '.' . $extension;
                        Storage::disk('public')->put($path, $image);

                        // Ensure storage link exists
                        if (!file_exists(public_path('storage'))) {
                            throw new Exception('Storage link not found. Please run "php artisan storage:link" command');
                        }

                        // Generate public URL that can be accessed directly
                        $url = url('storage/' . $path);

                        // Verify file was saved successfully
                        if (!Storage::disk('public')->exists($path)) {
                            throw new Exception('Failed to save attachment to storage');
                        }
                        $attachments = [
                            'id_jobsheet' => $request->id_jobsheet,
                            'file_name' => $file_name,
                            'url' => $url,
                            'public_id' => $path, // Store the path as public_id for future reference
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ];
                        DB::table('attachments_jobsheet')->insert($attachments);
                    }
                }
            }
            if ($request->has('cost') && is_array($request->cost) && count($request->cost) > 0) {
                foreach ($request->cost as $cost) {
                    $dataCost = [
                        'id_jobsheet' => $request->id_jobsheet,
                        'id_typecost' => $cost['id_typecost'],
                        'cost_value' => $cost['cost_value'],
                        'charge_by' => $cost['charge_by'] ?? null,
                        'description' => $cost['description'] ?? null,
                        'id_vendor' => $cost['id_vendor'],
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ];
                    DB::table('cost_jobsheet')->insert($dataCost);
                }
            }
            $updateJobsheet = DB::table('jobsheet')
                ->where('id_jobsheet', $request->id_jobsheet)
                ->update([
                    'remarks' => $request->remarks ?? null,
                    'status_jobsheet' => 'js_resubmitted',
                    'status_approval' => 'js_pending',
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ]);
            if (!$updateJobsheet) {
                throw new Exception('Failed to update jobsheet');
            }

            $deleteApproval = DB::table('approval_jobsheet')->where('id_jobsheet', $request->id_jobsheet)->delete();
            if ($deleteApproval === false) {
                throw new Exception('Failed to delete existing approvals');
            }

            $id_position = Auth::user()->id_position;
            $id_division = Auth::user()->id_division;
            if (!$id_position || !$id_division) {
                throw new Exception('Invalid user position or division');
            }
            $flow_approval = DB::table('flowapproval_jobsheet')
                ->where(['request_position' => $id_position, 'request_division' => $id_division])
                ->first();
            if (!$flow_approval) {
                throw new Exception('No flow approval found for the user position and division');
            } else {
                $detailApproval = DB::table('detailflowapproval_jobsheet')
                    ->where('id_flowapproval_jobsheet', $flow_approval->id_flowapproval_jobsheet)
                    ->get();
                foreach ($detailApproval as $approval) {
                    $approval = [
                        'id_jobsheet' => $request->id_jobsheet,
                        'approval_position' => $approval->approval_position,
                        'approval_division' => $approval->approval_division,
                        'step_no' => $approval->step_no,
                        'status' => 'pending',
                        'created_by' => Auth::id(),
                    ];
                    DB::table('approval_jobsheet')->insert($approval);
                }
            }


            return ResponseHelper::success('Jobsheet resubmitted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
