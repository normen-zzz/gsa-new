<?php

namespace App\Http\Controllers\flow\salesorder;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Auth;

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
                'id_awb' => 'required|integer|exists:awb,id_awb',
                'remarks' => 'nullable|string|max:255',
                'attachments' => 'required|array',
                'attachments.*.image' => 'nullable|string',
                'selling' => 'required|array',
                'selling.*.id_typeselling' => 'required|integer|exists:typeselling,id_typeselling',
                'selling.*.selling_value' => 'required|numeric|min:0',
                'selling.*.charge_by' => 'nullable|in:chargeable_weight,gross_weight,awb',
                'selling.*.description' => 'nullable|string|max:255'
            ]);

            $awb = DB::table('awb')->where('id_awb', $request->id_awb)->first();
            $id_job = $awb->id_job ?? null;
            $id_shippinginstruction = DB::table('job')->where('id_job', $id_job)->value('id_shippinginstruction');

            $dataSalesorder = [
                'id_shippinginstruction' => $id_shippinginstruction,
                'id_job' => $id_job,
                'id_awb' => $request->id_awb,
                'remarks' => $request->remarks,
                'created_by' => Auth::id(),
                'status' => 'so_created_by_sales'
            ];
            $insertSalesorder = DB::table('salesorder')->insertGetId($dataSalesorder);
            if ($insertSalesorder) {
                if (isset($request->attachments) && is_array($request->attachments)) {
                    foreach ($request->attachments as $attachment) {
                        $file_name = time() . '_' . $insertSalesorder;
                        $imageData = $attachment['image'] ?? null;
                        if (!$imageData) {
                            throw new Exception('Image data is required for attachments');
                        }
                        $cloudinaryImage = Cloudinary::uploadApi()->upload($imageData, [
                            'folder' => 'salesorders',
                        ]);
                        $url = $cloudinaryImage['secure_url'] ?? null;
                        $publicId = $cloudinaryImage['public_id'] ?? null;

                        $attachments = [
                            'id_salesorder' => $insertSalesorder, // Will be set after sales order creation
                            'file_name' => $file_name,
                            'url' => $url,
                            'public_id' => $publicId,
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
                ->orderBy('step_no', 'asc')
                ->get();
            if ($flow_approval->isEmpty()) {
                throw new Exception('No flow approval found for the user position and division');
            } else {
                foreach ($flow_approval as $approval) {
                    $approval = [
                        'id_salesorder' => $insertSalesorder,
                        'approval_position' => $approval->approval_position,
                        'approval_division' => $approval->approval_division,
                        'step_no' => $approval->step_no,
                        'next_step' => $approval->next_step,
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
            return ResponseHelper::error($e);
        }
    }

    public function getSalesorder(Request $request)
    {
        $limit = $request->input('limit', 10);
        $searchKey = $request->input('searchKey', '');

        $select = [
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
            'a.status'
        ];


        $salesorders = DB::table('salesorder AS a')
            ->select($select)
            ->leftJoin('users AS b', 'a.created_by', '=', 'b.id_user')
            ->leftJoin('users AS d', 'a.deleted_by', '=', 'd.id_user')
            ->leftJoin('awb AS c', 'a.id_awb', '=', 'c.id_awb')
            ->leftJoin('customers AS cu', 'c.agent', '=', 'cu.id_customer')
            ->when($searchKey, function ($query, $searchKey) {
                $query->where('cu.name_customer', 'like', "%{$searchKey}%");
            })
            ->paginate($limit);

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
                'approval_salesorder.next_step',
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
                ->get();
            $item->attachments_salesorder = $attachments;
            $item->selling_salesorder = $selling;
            $item->approval_salesorder = $approval_salesorder;
            return $item;
        });
        return ResponseHelper::success('Sales orders retrieved successfully', $salesorders);
    }

    public function getSalesorderById(Request $request)
    {
        $id = $request->input('id_salesorder');

        $select = [
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
            'a.status'
        ];

        $salesorder = DB::table('salesorder AS a')
            ->leftJoin('users AS b', 'a.created_by', '=', 'b.id_user')
            ->leftJoin('users AS d', 'a.deleted_by', '=', 'd.id_user')
            ->leftJoin('awb AS c', 'a.id_awb', '=', 'c.id_awb')
            ->leftJoin('customers AS cu', 'c.agent', '=', 'cu.id_customer')
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

        $selectApproval = [
            'approval_salesorder.id_approval_salesorder',
            'approval_salesorder.id_salesorder',
            'approval_salesorder.approval_position',
            'approval_position.name AS approval_position_name',
            'approval_salesorder.approval_division',
            'approval_division.name AS approval_division_name',
            'approval_salesorder.step_no',
            'approval_salesorder.next_step',
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
                $deleteOnCloudinary = Cloudinary::uploadApi()->destroy($attachment->public_id);
                if (!$deleteOnCloudinary) {
                    throw new Exception('Failed to delete attachment from Cloudinary');
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

            $changesSelling = [];
            $changesAttachments = [];

            if ($request->has('selling')) {
                foreach ($request->selling as $selling) {
                    if ($selling['id_selling_salesorder']) {
                        $sellingData = DB::table('selling_salesorder')->where('id_selling_salesorder', $selling['id_selling_salesorder'])->first();
                        // Update existing selling record
                        DB::table('selling_salesorder')
                            ->where('id_selling_salesorder', $selling['id_selling_salesorder'])
                            ->update([
                                'id_typeselling' => $selling['id_typeselling'],
                                'selling_value' => $selling['selling_value'],
                                'charge_by' => $selling['charge_by'],
                                'description' => $selling['description'] ?? null,
                                'updated_by' => Auth::id(),
                                'updated_at' => now(),
                            ]);

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
                        DB::table('selling_salesorder')->insert([
                            'id_salesorder' => $request->id_salesorder,
                            'id_typeselling' => $selling['id_typeselling'],
                            'selling_value' => $selling['selling_value'],
                            'charge_by' => $selling['charge_by'],
                            'description' => $selling['description'] ?? null,
                            'created_by' => Auth::id(),
                            'created_at' => now(),
                        ]);
                        $changesSelling[] = [
                            'id_selling_salesorder' => $selling['id_selling_salesorder'],
                            'old' => null,
                            'new' => [
                                'id_typeselling' => $selling['id_typeselling'],
                                'selling_value' => $selling['selling_value'],
                                'charge_by' => $selling['charge_by'],
                                'description' => $selling['description'] ?? null,
                                'created_by' => Auth::id(),
                                'created_at' => now(),
                            ]
                        ];
                    }
                }

            }

            if ($request->has('attachments')) {
                foreach ($request->attachments as $attachment) {

                    $uploadToCloudinary = Cloudinary::uploadApi()->upload($attachment['image'], [
                        'folder' => 'salesorders',
                    ]);
                    $file_name = time() . '_' . $request->id_salesorder;
                    $insert = DB::table('attachments_salesorder')->insert([
                        'id_salesorder' => $request->id_salesorder,
                        'file_name' => $file_name,
                        'url' => $uploadToCloudinary['secure_url'],
                        'public_id' => $uploadToCloudinary['public_id'],
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ]);

                    if (!$insert) {
                        $destroyOnCloudinary = Cloudinary::uploadApi()->destroy($uploadToCloudinary['public_id']);
                        throw new Exception('Failed to insert attachment');
                    }
                }
                $changesAttachments[] = [
                    'id_attachment_salesorder' => $attachment['id_attachment_salesorder'],
                    'old' => null,
                    'new' => [
                        'id_salesorder' => $request->id_salesorder,
                        'file_name' => $file_name,
                        'url' => $uploadToCloudinary['secure_url'],
                        'public_id' => $uploadToCloudinary['public_id'],
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ]
                ];
            }
            $log = [
                'id_salesorder' => $request->id_salesorder,
                'action' => [
                    'type' => 'update',
                    'data' => [
                        'selling' => $changesSelling,
                        'attachments' => $changesAttachments
                    ]
                ],
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
                } else{
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
                            'approved_by' => Auth::id(),
                            'updated_at' => now(),
                        ]);

                    if (!$update) {
                        throw new Exception('Failed to update approval status because');
                    } else{
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
                        DB::table('log_salesorder')->insert($log);
                    }
                } else{
                    throw new Exception('You are not authorized to update this approval status');
                }
            }



            DB::commit();
            return ResponseHelper::success('Sales order approved successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
