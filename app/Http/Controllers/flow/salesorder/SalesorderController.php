<?php

namespace App\Http\Controllers\flow\salesorder;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;


date_default_timezone_set('Asia/Jakarta');

class SalesorderController extends Controller
{

    public function createSalesorder(Request $request)
    {
        DB::beginTransaction();

        try {
            // Logic to create a sales order
            // Validate the request data
            $request->validate([
                'id_shippinginstruction' => 'required|integer|exists:shippinginstruction,id_shippinginstruction',
                'remarks' => 'nullable|string|max:255',
                'attachments' => 'required|array',
                'attachments.*.image' => 'nullable|string',
                'selling' => 'required|array',
                'selling.*.id_typeselling' => 'required|integer|exists:typeselling,id_typeselling',
                'selling.*.selling_value' => 'required|numeric|min:0',
                'selling.*.charge_by' => 'nullable|in:chargeable_weight,gross_weight,awb',
                'selling.*.description' => 'nullable|string|max:255'
            ]);

            $shippinginstruction = DB::table('shippinginstruction')->where('id_shippinginstruction', $request->id_shippinginstruction)->first();

            $dataSalesorder = [
                'id_shippinginstruction' => $request->id_shippinginstruction,
                'no_salesorder' => $shippinginstruction->no_shippinginstruction,
                'remarks' => $request->remarks,
                'created_by' => Auth::id(),
            ];
            $insertSalesorder = DB::table('salesorder')->insertGetId($dataSalesorder);
            if ($insertSalesorder) {
                if (isset($request->attachments) && is_array($request->attachments) && count($request->attachments) > 0) {
                    $no = 1;
                    foreach ($request->attachments as $attachment) {
                        // Generate a unique filename with timestamp
                        $file_name = time() . '/' . $no . '_' . $insertSalesorder;
                        $no++;
                        // Decode the base64 image
                        $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $attachment['image']));
                        $extension = explode('/', mime_content_type($attachment['image']))[1];

                        // Save file to public storage
                        $path = 'salesorders/' . $file_name . '.' . $extension;
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
                            'id_salesorder' => $insertSalesorder,
                            'file_name' => $file_name,
                            'url' => $url,
                            'public_id' => $path, // Store the path as public_id for future reference
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ];
                        DB::table('attachments_salesorder')->insert($attachments);
                    }
                } else {
                    throw new Exception('Invalid attachments format');
                }
                $selling = $request->selling;
                foreach ($selling as $item) {
                    $dataSelling = [
                        'id_salesorder' => $insertSalesorder,
                        'id_typeselling' => $item['id_typeselling'],
                        'selling_value' => $item['selling_value'],
                        'charge_by' => $item['charge_by'],
                        'description' => $item['description'] ?? null,
                        'created_by' => Auth::id(),
                    ];
                    DB::table('selling_salesorder')->insert($dataSelling);
                }
            } else {
                throw new Exception('Failed to create sales order');
            }
            $id_position = Auth::user()->id_position;
            $id_division = Auth::user()->id_division;
            if (!$id_position || !$id_division) {
                throw new Exception('Invalid user position or division');
            }
            $flow_approval = DB::table('flowapproval_salesorder')
                ->where(['request_position' => $id_position, 'request_division' => $id_division])
                ->first();
            if (!$flow_approval) {
                throw new Exception('No flow approval found for the user position and division');
            } else {

                $detail_flowapproval = DB::table('detailflowapproval_salesorder')
                    ->where('id_flowapproval_salesorder', $flow_approval->id_flowapproval_salesorder)
                    ->get();
                foreach ($detail_flowapproval as $approval) {
                    $approval = [
                        'id_salesorder' => $insertSalesorder,
                        'approval_position' => $approval->approval_position,
                        'approval_division' => $approval->approval_division,
                        'step_no' => $approval->step_no,
                        'status' => 'pending',
                        'created_by' => Auth::id(),
                    ];
                    DB::table('approval_salesorder')->insert($approval);
                }
            }



            DB::commit();
            return ResponseHelper::success('Sales order created successfully', null, 201);
        } catch (Exception $e) {
            DB::rollBack();
            // Remove image if exists
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            return ResponseHelper::error($e);
        }
    }

    public function getSalesorder(Request $request)
    {
        $limit = $request->input('limit', 10);
        $searchKey = $request->input('searchKey', '');

        $user = DB::table('users')->where('id_user', Auth::id())->first();
        $division = DB::table('divisions')->where('id_division', $user->id_division)->first();
        if ($division->name == 'Key Account' || $division->name == 'Sales') {
            $id_user = Auth::id();
        } else {
            $id_user = null;
        }

        $select = [
            'a.id_salesorder',
            'a.no_salesorder',
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
            'a.status_so',
            'a.status_approval'
        ];


        $salesorders = DB::table('salesorder AS a')
            ->select($select)
            ->leftJoin('users AS b', 'a.created_by', '=', 'b.id_user')
            ->leftJoin('users AS d', 'a.deleted_by', '=', 'd.id_user')
            ->leftJoin('awb AS c', 'a.id_awb', '=', 'c.id_awb')
            ->leftJoin('customers AS cu', 'c.agent', '=', 'cu.id_customer')
            ->when($searchKey, function ($query, $searchKey) {
                $query->where('cu.name_customer', 'like', "%{$searchKey}%");
            });
        if ($id_user) {
            $salesorders->where('a.created_by', $id_user);
        }
        $salesorders = $salesorders->paginate($limit);

        if ($salesorders->isEmpty()) {
            return ResponseHelper::success('No sales orders found', null, 200);
        } else {


            $salesorders->getCollection()->transform(function ($item) {
                $selectAttachments = [
                    'attachments_salesorder.id_attachment_salesorder',
                    'attachments_salesorder.id_salesorder',
                    'attachments_salesorder.file_name',
                    'attachments_salesorder.url',
                    'attachments_salesorder.public_id',
                    'attachments_salesorder.created_by',
                    'created_by.name AS created_by_name',
                    'attachments_salesorder.created_at',
                    'attachments_salesorder.deleted_at',
                    'deleted_by.name AS deleted_by_name'

                ];

                $attachments = DB::table('attachments_salesorder')
                    ->leftJoin('users AS created_by', 'attachments_salesorder.created_by', '=', 'created_by.id_user')
                    ->leftJoin('users AS deleted_by', 'attachments_salesorder.deleted_by', '=', 'deleted_by.id_user')
                    ->where('id_salesorder', $item->id_salesorder)
                    ->select($selectAttachments)
                    ->get();



                $selectSelling = [
                    'selling_salesorder.id_selling_salesorder',
                    'selling_salesorder.id_salesorder',
                    'selling_salesorder.id_typeselling',
                    'ts.name AS typeselling_name',
                    'selling_salesorder.selling_value',
                    'selling_salesorder.charge_by',
                    'selling_salesorder.description',
                    'selling_salesorder.created_by',
                    'created_by.name AS created_by_name',
                    'selling_salesorder.created_at'
                ];

                $selling = DB::table('selling_salesorder')
                    ->where('id_salesorder', $item->id_salesorder)
                    ->join('typeselling AS ts', 'selling_salesorder.id_typeselling', '=', 'ts.id_typeselling')
                    ->leftJoin('users AS created_by', 'selling_salesorder.created_by', '=', 'created_by.id_user')
                    ->select($selectSelling)
                    ->get();

                $selectApproval = [
                    'approval_salesorder.id_approval_salesorder',
                    'approval_salesorder.id_salesorder',
                    'approval_salesorder.approval_position',
                    'approval_position.name AS approval_position_name',
                    'approval_salesorder.approval_division',
                    'approval_division.name AS approval_division_name',
                    'approval_salesorder.step_no',

                    'approval_salesorder.created_by',
                    'created_by.name AS created_by_name',
                    'approval_salesorder.approved_by',
                    'approved_by.name AS approved_by_name',
                    'approval_salesorder.status',

                ];

                $approval_salesorder = DB::table('approval_salesorder')
                    ->select($selectApproval)
                    ->leftJoin('users AS approval_position', 'approval_salesorder.approval_position', '=', 'approval_position.id_user')
                    ->leftJoin('users AS approval_division', 'approval_salesorder.approval_division', '=', 'approval_division.id_user')
                    ->leftJoin('users AS approved_by', 'approval_salesorder.approved_by', '=', 'approved_by.id_user')
                    ->leftJoin('users AS created_by', 'approval_salesorder.created_by', '=', 'created_by.id_user')
                    ->where('id_salesorder', $item->id_salesorder)
                    ->orderBy('approval_salesorder.step_no', 'ASC')
                    ->get();

                $select = [
                    'shippinginstruction.id_shippinginstruction',
                    'shippinginstruction.agent',
                    'agent.name_customer as agent_name',
                    'shippinginstruction.data_agent as id_data_agent',
                    'data_agent.pic as data_agent_pic',
                    'data_agent.email as data_agent_email',
                    'data_agent.phone as data_agent_phone',
                    'data_agent.tax_id as data_agent_tax_id',
                    'data_agent.address as data_agent_address',
                    'shippinginstruction.consignee',
                    'shippinginstruction.airline',
                    'airlines.name as airline_name',
                    'shippinginstruction.type',
                    'shippinginstruction.etd',
                    'shippinginstruction.eta',
                    'shippinginstruction.pol',
                    'pol.name_airport as pol_name',
                    'pol.code_airport as pol_code',
                    'shippinginstruction.pod',
                    'pod.name_airport as pod_name',
                    'pod.code_airport as pod_code',
                    'shippinginstruction.commodity',
                    'shippinginstruction.gross_weight',
                    'shippinginstruction.chargeable_weight',
                    'shippinginstruction.pieces',
                    'shippinginstruction.dimensions',
                    'shippinginstruction.special_instructions',
                    'shippinginstruction.created_by',
                    'created_by.name as created_by_name',
                    'shippinginstruction.updated_by',
                    'updated_by.name as updated_by_name',
                    'shippinginstruction.received_by',
                    'received_by.name as received_by_name',
                    'shippinginstruction.deleted_by',
                    'deleted_by.name as deleted_by_name',
                    'shippinginstruction.created_at',
                    'shippinginstruction.updated_at',
                    'shippinginstruction.received_at',
                    'shippinginstruction.deleted_at'
                ];

                //get pending approval yang paling atas


                $shippingInstruction = DB::table('shippinginstruction')
                    ->select(
                        $select
                    )
                    ->leftJoin('customers as agent', 'shippinginstruction.agent', '=', 'agent.id_customer')
                    ->leftJoin('data_customer as data_agent', 'shippinginstruction.data_agent', '=', 'data_agent.id_datacustomer')
                    ->leftJoin('airports as pol', 'shippinginstruction.pol', '=', 'pol.id_airport')
                    ->leftJoin('airports as pod', 'shippinginstruction.pod', '=', 'pod.id_airport')
                    ->leftJoin('users as created_by', 'shippinginstruction.created_by', '=', 'created_by.id_user')
                    ->leftJoin('users as updated_by', 'shippinginstruction.updated_by', '=', 'updated_by.id_user')
                    ->leftJoin('users as received_by', 'shippinginstruction.received_by', '=', 'received_by.id_user')
                    ->leftJoin('users as deleted_by', 'shippinginstruction.deleted_by', '=', 'deleted_by.id_user')
                    ->leftJoin('airlines', 'shippinginstruction.airline', '=', 'airlines.id_airline')
                    ->where('id_shippinginstruction', $item->id_shippinginstruction)
                    ->first();

                $dimension_shippinginstruction = DB::table('dimension_shippinginstruction')
                    ->where('id_shippinginstruction', $item->id_shippinginstruction)
                    ->get();

                $pendingApproval = DB::table('approval_salesorder')
                    ->where('id_salesorder', $item->id_salesorder)
                    ->where('status', 'pending')
                    ->orderBy('step_no', 'ASC')
                    ->first();

                $position = Auth::user()->id_position;
                $division = Auth::user()->id_division;
                if ($pendingApproval && $pendingApproval->approval_position == $position && $pendingApproval->approval_division == $division) {
                    $item->is_approver = true;
                    $item->id_approval_salesorder = $pendingApproval->id_approval_salesorder;
                } else {
                    $item->is_approver = false;
                }

                // decode

                $shippingInstruction->dimensions = $dimension_shippinginstruction;
                $item->shippinginstruction = $shippingInstruction;
                $item->attachments_salesorder = $attachments;
                $item->selling_salesorder = $selling;
                $item->approval_salesorder = $approval_salesorder;



                return $item;
            });
            return ResponseHelper::success('Sales orders retrieved successfully', $salesorders);
        }
    }

    public function getSalesorderById(Request $request)
    {
        $id = $request->input('id_salesorder');

        $id_shippinginstruction = DB::table('salesorder')
            ->where('id_salesorder', $id)
            ->value('id_shippinginstruction');
        $id_job = DB::table('job')
            ->where('id_shippinginstruction', $id_shippinginstruction)
            ->value('id_job');
        $id_awb = DB::table('awb')
            ->where('id_job', $id_job)
            ->value('id_awb');

        $select = [
            'a.id_salesorder',
            'a.id_shippinginstruction',
            'a.remarks',
            'a.created_at',
            'a.created_by',
            'b.name AS created_by_name',
            'a.deleted_at',
            'a.deleted_by',
            'd.name AS deleted_by_name',
            'a.status_so',
            'a.status_approval'
        ];

        $salesorder = DB::table('salesorder AS a')
            ->leftJoin('users AS b', 'a.created_by', '=', 'b.id_user')
            ->leftJoin('users AS d', 'a.deleted_by', '=', 'd.id_user')
            ->select($select)
            ->where('id_salesorder', $id)
            ->first();

        if (!$salesorder) {
            return ResponseHelper::success('Sales order not found', null, 404);
        }

        $selectAttachments = [
            'attachments_salesorder.id_attachment_salesorder',
            'attachments_salesorder.id_salesorder',
            'attachments_salesorder.file_name',
            'attachments_salesorder.url',
            'attachments_salesorder.public_id',
            'attachments_salesorder.created_by',
            'created_by.name AS created_by_name',
            'attachments_salesorder.created_at',
            'attachments_salesorder.deleted_at',
            'deleted_by.name AS deleted_by_name'

        ];

        $attachments = DB::table('attachments_salesorder')
            ->leftJoin('users AS created_by', 'attachments_salesorder.created_by', '=', 'created_by.id_user')
            ->leftJoin('users AS deleted_by', 'attachments_salesorder.deleted_by', '=', 'deleted_by.id_user')
            ->where('id_salesorder', $id)
            ->select($selectAttachments)
            ->get();

        $selectSelling = [
            'selling_salesorder.id_selling_salesorder',
            'selling_salesorder.id_salesorder',
            'selling_salesorder.id_typeselling',
            'ts.name AS typeselling_name',
            'ts.initials AS typeselling_initials',
            'selling_salesorder.selling_value',
            'selling_salesorder.charge_by',
            'selling_salesorder.description',
            'selling_salesorder.created_by',
            'created_by.name AS created_by_name',
            'selling_salesorder.created_at'
        ];

        $selling = DB::table('selling_salesorder')
            ->where('id_salesorder', $id)
            ->join('typeselling AS ts', 'selling_salesorder.id_typeselling', '=', 'ts.id_typeselling')
            ->leftJoin('users AS created_by', 'selling_salesorder.created_by', '=', 'created_by.id_user')
            ->select($selectSelling)
            ->get();

        // Get shipping instruction data first
        $shippingInstruction = DB::table('shippinginstruction')
            ->where('id_shippinginstruction', DB::table('salesorder')->where('id_salesorder', $id)->value('id_shippinginstruction'))
            ->first();

        // Transform each item in the collection to add the total field
        $selling->transform(function ($item) use ($shippingInstruction) {
            $item->total = 0;

            if ($shippingInstruction) {
                switch ($item->charge_by) {
                    case 'chargeable_weight':
                        $item->total = $item->selling_value * $shippingInstruction->chargeable_weight;
                        break;
                    case 'gross_weight':
                        $item->total = $item->selling_value * $shippingInstruction->gross_weight;
                        break;
                    case 'awb':
                        $item->total = $item->selling_value;
                        break;
                    default:
                        break;
                }
            }

            return $item;
        });

        $selectApproval = [
            'approval_salesorder.id_approval_salesorder',
            'approval_salesorder.id_salesorder',
            'approval_salesorder.approval_position',
            'approval_position.name AS approval_position_name',
            'approval_salesorder.approval_division',
            'approval_division.name AS approval_division_name',
            'approval_salesorder.step_no',
            'approval_salesorder.created_by',
            'created_by.name AS created_by_name',
            'approval_salesorder.approved_by',
            'approved_by.name AS approved_by_name',
            'approval_salesorder.status',

        ];

        $approval_salesorder = DB::table('approval_salesorder')
            ->select($selectApproval)
            ->leftJoin('users AS approval_position', 'approval_salesorder.approval_position', '=', 'approval_position.id_user')
            ->leftJoin('users AS approval_division', 'approval_salesorder.approval_division', '=', 'approval_division.id_user')
            ->leftJoin('users AS approved_by', 'approval_salesorder.approved_by', '=', 'approved_by.id_user')
            ->leftJoin('users AS created_by', 'approval_salesorder.created_by', '=', 'created_by.id_user')
            ->where('id_salesorder', $id)
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
            ->where('id_awb', $id_awb)
            ->first();


        $select = [
            'shippinginstruction.id_shippinginstruction',
            'shippinginstruction.agent',
            'agent.name_customer as agent_name',
            'shippinginstruction.data_agent as id_data_agent',
            'data_agent.pic as data_agent_pic',
            'data_agent.email as data_agent_email',
            'data_agent.phone as data_agent_phone',
            'data_agent.tax_id as data_agent_tax_id',
            'data_agent.address as data_agent_address',
            'shippinginstruction.consignee',
            'shippinginstruction.airline',
            'airlines.name as airline_name',
            'shippinginstruction.type',
            'shippinginstruction.etd',
            'shippinginstruction.eta',
            'shippinginstruction.pol',
            'pol.name_airport as pol_name',
            'pol.code_airport as pol_code',
            'shippinginstruction.pod',
            'pod.name_airport as pod_name',
            'pod.code_airport as pod_code',
            'shippinginstruction.commodity',
            'shippinginstruction.gross_weight',
            'shippinginstruction.chargeable_weight',
            'shippinginstruction.pieces',
            'shippinginstruction.dimensions',
            'shippinginstruction.special_instructions',
            'shippinginstruction.created_by',
            'created_by.name as created_by_name',
            'shippinginstruction.updated_by',
            'updated_by.name as updated_by_name',
            'shippinginstruction.received_by',
            'received_by.name as received_by_name',
            'shippinginstruction.deleted_by',
            'deleted_by.name as deleted_by_name',
            'shippinginstruction.created_at',
            'shippinginstruction.updated_at',
            'shippinginstruction.received_at',
            'shippinginstruction.deleted_at'
        ];

        $shippingInstruction = DB::table('shippinginstruction')
            ->select(
                $select
            )
            ->leftJoin('customers as agent', 'shippinginstruction.agent', '=', 'agent.id_customer')
            ->leftJoin('data_customer as data_agent', 'shippinginstruction.data_agent', '=', 'data_agent.id_datacustomer')
            ->leftJoin('airports as pol', 'shippinginstruction.pol', '=', 'pol.id_airport')
            ->leftJoin('airports as pod', 'shippinginstruction.pod', '=', 'pod.id_airport')
            ->leftJoin('users as created_by', 'shippinginstruction.created_by', '=', 'created_by.id_user')
            ->leftJoin('users as updated_by', 'shippinginstruction.updated_by', '=', 'updated_by.id_user')
            ->leftJoin('users as received_by', 'shippinginstruction.received_by', '=', 'received_by.id_user')
            ->leftJoin('users as deleted_by', 'shippinginstruction.deleted_by', '=', 'deleted_by.id_user')
            ->leftJoin('airlines', 'shippinginstruction.airline', '=', 'airlines.id_airline')
            ->where('id_shippinginstruction', $salesorder->id_shippinginstruction)
            ->first();

        $route = DB::table('routes')
            ->where('airline', $shippingInstruction->airline)
            ->where('pol', $shippingInstruction->pol)
            ->where('pod', $shippingInstruction->pod)
            ->first();
        if (!$route) {
            // throw new Exception('Route not found');
        } else {
            $getWeightBrackets = DB::table('weight_bracket_costs')
                ->where('min_weight', '>=', $shippingInstruction->chargeable_weight)
                ->orderBy('min_weight', 'ASC')
                ->first();
            if (!$getWeightBrackets) {
                // throw new Exception('Weight bracket not found');
            } else {
                $selectCost = [
                    'cost.id_cost',
                    'cost.id_weight_bracket_cost',
                    'weight_bracket_costs.min_weight',
                    'cost.id_typecost',
                    'typecost.initials as typecost_initials',
                    'typecost.name as typecost_name',
                    'cost.id_route',
                    'pol.name_airport as pol_name',
                    'pod.name_airport as pod_name',
                    'cost.cost_value',
                    'cost.charge_by'
                ];
                $getCost = DB::table('cost')
                    ->select($selectCost)
                    ->leftJoin('weight_bracket_costs', 'cost.id_weight_bracket_cost', '=', 'weight_bracket_costs.id_weight_bracket_cost')
                    ->leftJoin('typecost', 'cost.id_typecost', '=', 'typecost.id_typecost')
                    ->leftJoin('routes', 'cost.id_route', '=', 'routes.id_route')
                    ->leftJoin('airports as pol', 'routes.pol', '=', 'pol.id_airport')
                    ->leftJoin('airports as pod', 'routes.pod', '=', 'pod.id_airport')
                    ->where('cost.id_route', $route->id_route)
                    ->where('cost.id_weight_bracket_cost', $getWeightBrackets->id_weight_bracket_cost)
                    ->get();
            }
        }
        if (isset($getCost)) {
            $salesorder->data_cost = $getCost;
        }
        if (isset($getWeightBrackets)) {
            $salesorder->weight_bracket_cost = $getWeightBrackets;
        }
        $salesorder->route = $route;

        if ($id_awb) {
            $salesorder->data_awb = $awb;
        } else {
            $salesorder->data_awb = null;
        }

        if ($shippingInstruction) {
            // decode dimension
            $shippingInstruction->dimensions = json_decode($shippingInstruction->dimensions);
            $salesorder->data_shippinginstruction = $shippingInstruction;
        } else {
            $salesorder->data_shippinginstruction = null;
        }

        $pendingApproval = DB::table('approval_salesorder')
            ->where('id_salesorder', $salesorder->id_salesorder)
            ->where('status', 'pending')
            ->orderBy('step_no', 'ASC')
            ->first();

        $position = Auth::user()->id_position;
        $division = Auth::user()->id_division;
        if ($pendingApproval && $pendingApproval->approval_position == $position && $pendingApproval->approval_division == $division) {
            $salesorder->is_approver = true;
            $salesorder->id_approval_salesorder = $pendingApproval->id_approval_salesorder;
        } else {
            $salesorder->is_approver = false;
        }


        $salesorder->attachments_salesorder = $attachments;
        $salesorder->selling_salesorder = $selling;
        $salesorder->approval_salesorder = $approval_salesorder;

        return ResponseHelper::success('Sales order retrieved successfully', $salesorder, 200);
    }

    public function deleteAttachmentSalesorder(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_attachment_salesorder' => 'required|integer|exists:attachments_salesorder,id_attachment_salesorder',
            ]);

            $id_attachment_salesorder = $request->id_attachment_salesorder;

            $attachment = DB::table('attachments_salesorder')->where('id_attachment_salesorder', $id_attachment_salesorder)->first();

            $update = DB::table('attachments_salesorder')->where('id_attachment_salesorder', $id_attachment_salesorder)
                ->update(['deleted_at' => now(), 'deleted_by' => Auth::id()]);
            if ($update) {
                // Delete file from storage using public_id (which is the path in this case)
                if (Storage::disk('public')->exists($attachment->public_id)) {
                    Storage::disk('public')->delete($attachment->public_id);
                }
                $log = [
                    'id_attachment_salesorder' => $id_attachment_salesorder,
                    'action' => [
                        'type' => 'delete',
                        'description' => 'Deleted attachment sales order'
                    ],
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                ];
                DB::table('log_attachments_salesorder')->insert($log);
            }


            DB::commit();
            return ResponseHelper::success('Attachment deleted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function updateSalesorder(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_salesorder' => 'required|integer|exists:salesorder,id_salesorder',
                'selling' => 'nullable|array',
                'selling.*.id_selling_salesorder' => 'nullable|integer|exists:selling_salesorder,id_selling_salesorder',
                'selling.*.id_typeselling' => 'required|integer|exists:typeselling,id_typeselling',
                'selling.*.selling_value' => 'required|numeric',
                'selling.*.charge_by' => 'required|string',
                'selling.*.description' => 'nullable|string',
                'attachments' => 'nullable|array',
                'attachments.*.image' => 'required|string',
            ]);

            $changesSelling = NULL;
            $changesAttachments = NULL;

            if ($request->has('selling')) {
                foreach ($request->selling as $selling) {
                    if (isset($selling['id_selling_salesorder']) && $selling['id_selling_salesorder'] != null) {
                        $sellingData = DB::table('selling_salesorder')->where('id_selling_salesorder', $selling['id_selling_salesorder'])->first();
                        // Update existing selling record
                        $updateSelling =  DB::table('selling_salesorder')
                            ->where('id_selling_salesorder', $selling['id_selling_salesorder'])
                            ->update([
                                'id_typeselling' => $selling['id_typeselling'],
                                'selling_value' => $selling['selling_value'],
                                'charge_by' => $selling['charge_by'],
                                'description' => $selling['description'] ?? null,
                                'updated_by' => Auth::id(),
                                'updated_at' => now(),
                            ]);
                        if (!$updateSelling) {
                            throw new Exception('Failed to update selling record');
                        }

                        $changesSelling[] = [
                            'id_selling_salesorder' => $selling['id_selling_salesorder'],
                            'old' => $sellingData,
                            'new' => [
                                'id_typeselling' => $selling['id_typeselling'],
                                'selling_value' => $selling['selling_value'],
                                'charge_by' => $selling['charge_by'],
                                'description' => $selling['description'] ?? null,
                                'updated_by' => Auth::id(),
                                'updated_at' => now(),
                            ]
                        ];
                    } else {
                        // Create new selling record
                        $insertSelling = DB::table('selling_salesorder')->insertGetId([
                            'id_salesorder' => $request->id_salesorder ?? null,
                            'id_typeselling' => $selling['id_typeselling'] ?? null,
                            'selling_value' => $selling['selling_value'] ?? null,
                            'charge_by' => $selling['charge_by'] ?? null,
                            'description' => $selling['description'] ?? null,
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ]);
                        $changesSelling[] = [
                            'id_selling_salesorder' => $insertSelling ?? null,
                            'old' => null,
                            'new' => [
                                'id_typeselling' => $selling['id_typeselling'] ?? null,
                                'selling_value' => $selling['selling_value'] ?? null,
                                'charge_by' => $selling['charge_by'] ?? null,
                                'description' => $selling['description'] ?? null,
                                'created_by' => Auth::id(),
                                'created_at' => now(),
                            ]
                        ];
                    }
                }
            }

            if ($request->has('attachments')) {
                $no = 1;
                foreach ($request->attachments as $attachment) {
                    //    upload ke local
                    $file_name = time() . '/' . $no . '_' . $request->id_salesorder . '.jpg';
                    $no++;

                    // Decode the base64 image
                    $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $attachment['image']));

                    // Save file to public storage
                    $path = 'salesorders/' . $file_name;
                    Storage::disk('public')->put($path, $image);

                    $insert = DB::table('attachments_salesorder')->insertGetId([
                        'id_salesorder' => $request->id_salesorder,
                        'file_name' => $file_name,
                        'url' => url('storage/' . $path),
                        'public_id' => $path,
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ]);

                    if (!$insert) {
                        // Delete file from storage
                        if (Storage::disk('public')->exists($path)) {
                            Storage::disk('public')->delete($path);
                        }
                        throw new Exception('Failed to insert attachment');
                    } else {
                        $changesAttachments[] = [
                            'id_attachment_salesorder' => $insert,
                            'old' => null,
                            'new' => [
                                'file_name' => $file_name,
                                'url' => url('storage/' . $path),
                                'public_id' => $path,
                                'created_by' => Auth::id(),
                                'created_at' => now(),
                            ]
                        ];
                    }
                }
            }


            $log = [
                'id_salesorder' => $request->id_salesorder,
                'action' => json_encode([
                    'type' => 'update',
                    'data' => [
                        'selling' => $changesSelling,
                        'attachments' => $changesAttachments
                    ]
                ]),
                'created_by' => Auth::id(),
                'created_at' => now(),
            ];
            DB::table('log_salesorder')->insert($log);
            DB::commit();
            return ResponseHelper::success('Sales order updated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteSalesorder(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_salesorder' => 'required|integer|exists:salesorder,id_salesorder',
            ]);

            $update = DB::table('salesorder')
                ->where('id_salesorder', $request->id_salesorder)
                ->update([
                    'deleted_by' => Auth::id(),
                    'deleted_at' => now(),
                    'status' => 'so_deleted'
                ]);
            if ($update) {
                $attachments = DB::table('attachments_salesorder')
                    ->where('id_salesorder', $request->id_salesorder)
                    ->get();



                $log = [
                    'id_salesorder' => $request->id_salesorder,
                    'action' => [
                        'type' => 'delete',
                        'data' => [
                            'deleted_by' => Auth::id(),
                            'deleted_at' => now()
                        ]
                    ]
                ];
                DB::table('log_salesorder')->insert($log);
            } else {
                throw new Exception('Failed to delete sales order');
            }
            DB::commit();
            return ResponseHelper::success('Sales order deleted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function activateSalesorder(Request $request)
    {

        DB::beginTransaction();
        try {
            $request->validate([
                'id_salesorder' => 'required|integer|exists:salesorder,id_salesorder',
            ]);



            $update = DB::table('salesorder')
                ->where('id_salesorder', $request->id_salesorder)
                ->update([
                    'deleted_by' => null,
                    'deleted_at' => null,
                    'status' => 'so_created_by_sales'
                ]);
            if ($update) {
                $log = [
                    'id_salesorder' => $request->id_salesorder,
                    'action' => [
                        'type' => 'activate',
                        'data' => [
                            'activated_by' => Auth::id(),
                            'activated_at' => now()
                        ]
                    ]
                ];
                DB::table('log_salesorder')->insert($log);
            }
            DB::commit();
            return ResponseHelper::success('Sales order activated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function actionSalesorder(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_approval_salesorder' => 'required|integer|exists:approval_salesorder,id_approval_salesorder',
                'id_salesorder' => 'required|integer|exists:salesorder,id_salesorder',
                'remarks' => 'nullable|string|max:255',
                'status' => 'required|in:approved,rejected'
            ]);

            $approval = DB::table('approval_salesorder')
                ->where('id_approval_salesorder', $request->id_approval_salesorder)
                ->where('id_salesorder', $request->id_salesorder)
                ->first();

            if ($approval) {
                if ($approval->approval_position == Auth::user()->id_position && $approval->approval_division == Auth::user()->id_division) {
                    $update = DB::table('approval_salesorder')
                        ->where('id_approval_salesorder', $request->id_approval_salesorder)
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
                            // update status sales order to rejected
                            $updateSalesorder = DB::table('salesorder')
                                ->where('id_salesorder', $request->id_salesorder)
                                ->update([
                                    'status_approval' => 'so_rejected',
                                    'updated_at' => now(),
                                ]);
                            if (!$updateSalesorder) {
                                throw new Exception('Failed to update sales order status to rejected');
                            }
                        } else {
                            // Cek apakah ada pending approval lagi
                            $pendingApproval = DB::table('approval_salesorder')
                                ->where('id_salesorder', $request->id_salesorder)
                                ->where('status', 'pending')
                                ->orderBy('step_no', 'ASC')
                                ->first();
                            if (!$pendingApproval) {
                                // update status sales order to approved
                                $updateSalesorder = DB::table('salesorder')
                                    ->where('id_salesorder', $request->id_salesorder)
                                    ->update([
                                        'status_approval' => 'so_approved',
                                        'updated_at' => now(),
                                    ]);
                                if (!$updateSalesorder) {
                                    throw new Exception('Failed to update sales order status to approved');
                                }
                            }
                        }
                        $log = [
                            'id_salesorder' => $request->id_salesorder,
                            'action' => [
                                'type' => 'approve',
                                'data' => [
                                    'id_approval_salesorder' => $request->id_approval_salesorder,
                                    'approved_by' => Auth::id(),
                                    'approved_at' => now()
                                ]
                            ]
                        ];
                        $insertLog = DB::table('log_salesorder')->insert($log);
                        if (!$insertLog) {
                            throw new Exception('Failed to insert log_salesorder');
                        }
                    }
                } else {
                    throw new Exception('You are not authorized to update this approval status');
                }
            } else {
                throw new Exception('Approval record not found');
            }
            DB::commit();
            return ResponseHelper::success('Sales order approved successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function  resubmitSalesorder(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_salesorder' => 'required|integer|exists:salesorder,id_salesorder',
                'remarks' => 'nullable|string|max:255',
                'attachments' => 'required|array',
                'attachments.*.image' => 'nullable|string',
                'selling' => 'required|array',
                'selling.*.id_typeselling' => 'required|integer|exists:typeselling,id_typeselling',
                'selling.*.selling_value' => 'required|numeric|min:0',
                'selling.*.charge_by' => 'nullable|in:chargeable_weight,gross_weight,awb',
                'selling.*.description' => 'nullable|string|max:255'
            ]);

            $salesorder = DB::table('salesorder')->where('id_salesorder', $request->id_salesorder)->first();
            $attachments_salesorder = DB::table('attachments_salesorder')->where('id_salesorder', $request->id_salesorder)->whereNull('deleted_at')->get();
            $selling_salesorder = DB::table('selling_salesorder')->where('id_salesorder', $request->id_salesorder)->get();
            $approval_salesorder = DB::table('approval_salesorder')->where('id_salesorder', $request->id_salesorder)->get();

            // Validate if there is at least one attachment

            if (!$salesorder) {
                throw new Exception('Sales order not found');
            } else {
                if ($salesorder->status_approval != 'so_rejected') {
                    throw new Exception('Only rejected sales order can be resubmitted');
                }
                $deleteSelling = DB::table('selling_salesorder')->where('id_salesorder', $request->id_salesorder)->delete();
                if (!$deleteSelling) {
                    throw new Exception('Failed to delete selling sales order');
                }
                if (!$attachments_salesorder->isEmpty()) {
                    foreach ($attachments_salesorder as $attachment) {
                        // Delete file from storage using public_id (which is the path in this case)
                        if (Storage::disk('public')->exists($attachment->public_id)) {
                            Storage::disk('public')->delete($attachment->public_id);
                        }
                    }
                    $deleteAttachments = DB::table('attachments_salesorder')->where('id_salesorder', $request->id_salesorder)->delete();
                    if (!$deleteAttachments) {
                        throw new Exception('Failed to delete attachments sales order');
                    }
                }

                $deleteApproval = DB::table('approval_salesorder')->where('id_salesorder', $request->id_salesorder)->delete();
                if (!$deleteApproval) {
                    throw new Exception('Failed to delete approval sales order');
                }

                $dataSalesorder = [
                    'remarks' => $request->remarks ?? null,
                    'status_so' => 'so_resubmitted',
                    'status_approval' => 'pending',
                    'updated_at' => now(),
                ];
                $updateSalesorder = DB::table('salesorder')->where('id_salesorder', $request->id_salesorder)->update($dataSalesorder);
                if (!$updateSalesorder) {
                    throw new Exception('Failed to update sales order');
                } else {
                    if ($request->has('attachments')) {
                        $no = 1;
                        foreach ($request->attachments as $attachment) {
                            if (!isset($attachment['image']) || $attachment['image'] == null) {
                                throw new Exception('Attachment image is required');
                            } else {
                                //    upload ke local
                                $file_name = time() . '/' . $no . '_' . $request->id_salesorder . '.jpg';
                                $no++;

                                // Decode the base64 image
                                $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $attachment['image']));

                                // Save file to public storage
                                $path = 'salesorders/' . $file_name;
                                Storage::disk('public')->put($path, $image);
                                $attachment['image'] = $file_name;
                                $attachment['url'] = url('storage/' . $path);
                                $attachment['public_id'] = $path;
                            }
                            $dataAttachment = [
                                'id_salesorder' => $request->id_salesorder,
                                'file_name' => $attachment['image'],
                                'url' => $attachment['url'],
                                'public_id' => $attachment['public_id'],
                                'created_by' => Auth::id(),
                            ];
                            $insertAttachment = DB::table('attachments_salesorder')->insert($dataAttachment);
                            if (!$insertAttachment) {
                                throw new Exception('Failed to insert attachment');
                            }
                        }
                    }

                    if ($request->has('selling')) {
                        $dataSelling = [
                            'id_salesorder' => $request->id_salesorder,
                            'id_typeselling' => $request->selling['id_typeselling'],
                            'selling_value' => $request->selling['selling_value'],
                            'charge_by' => $request->selling['charge_by'],
                            'description' => $request->selling['description'],
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ];
                        $insertSelling = DB::table('selling_salesorder')->insert($dataSelling);
                        if (!$insertSelling) {
                            throw new Exception('Failed to insert selling data');
                        }
                    }

                    $id_position = Auth::user()->id_position;
                    $id_division = Auth::user()->id_division;
                    if (!$id_position || !$id_division) {
                        throw new Exception('Invalid user position or division');
                    }
                    $flow_approval = DB::table('flowapproval_salesorder')
                        ->where(['request_position' => $id_position, 'request_division' => $id_division])
                        ->first();
                    if (!$flow_approval) {
                        throw new Exception('No flow approval found for the user position and division');
                    } else {

                        $detail_flowapproval = DB::table('detailflowapproval_salesorder')
                            ->where('id_flowapproval_salesorder', $flow_approval->id_flowapproval_salesorder)
                            ->get();
                        foreach ($detail_flowapproval as $approval) {
                            $approval = [
                                'id_salesorder' => $request->id_salesorder,
                                'approval_position' => $approval->approval_position,
                                'approval_division' => $approval->approval_division,
                                'step_no' => $approval->step_no,
                                'status' => 'pending',
                                'created_by' => Auth::id(),
                            ];
                            DB::table('approval_salesorder')->insert($approval);
                        }
                    }
                }
            }
            DB::commit();
            return ResponseHelper::success('Sales order resubmitted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
