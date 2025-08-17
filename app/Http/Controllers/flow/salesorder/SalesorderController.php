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
                'id_shippinginstruction' => 'required|integer|exists:shippinginstruction,id_shippinginstruction',
                'id_job' => 'required|integer|exists:job,id_job',
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

            $dataSalesorder = [
                'id_shippinginstruction' => $request->id_shippinginstruction,
                'id_job' => $request->id_job,
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
            return ResponseHelper::success('hehe', null, 201);
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
            ->when($searchKey, function ($query, $searchKey) {
                $query->where('a.name', 'like', "%{$searchKey}%");
            })
            ->paginate($limit);

            $salesorders->getCollection()->transform(function ($item) {
               $attachment = DB::table('attachments_salesorder')
                    ->where('id_salesorder', $item->id_salesorder)
                    ->get();
                $selling = DB::table('selling_salesorder')
                    ->where('id_salesorder', $item->id_salesorder)
                    ->join('typeselling AS ts', 'selling_salesorder.id_typeselling', '=', 'ts.id_typeselling')
                    ->select('selling_salesorder.*', 'ts.name AS typeselling_name')
                    ->get();

                    $approval_salesorder = DB::table('approval_salesorder')
                        ->where('id_salesorder', $item->id_salesorder)
                        ->get();
                $item->attachments = $attachment;
                $item->selling = $selling;
                $item->approval = $approval_salesorder;
                return $item;
            });

            
            

        return ResponseHelper::success('Sales orders retrieved successfully', $salesorders);
    }
}
