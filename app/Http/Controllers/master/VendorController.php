<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use GuzzleHttp\Psr7\Response;
use App\Helpers\ResponseHelper;
use App\Models\master\VendorModel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class VendorController extends Controller
{
    public function createVendor(Request $request)
    {
        try {
            // Start a database transaction
            DB::beginTransaction();

            // Validate the request data
            $data = $request->validate([
                'name_vendor' => 'required|string|min:3|unique:vendors,name_vendor',
                'status' => 'required|boolean',
                'data_vendor' => 'nullable|array',
                'data_vendor.*.account_number' => 'nullable|numeric',
                'data_vendor.*.bank' => 'nullable|string|max:20',
                'data_vendor.*.pic' => 'required|string|max:100',
                'data_vendor.*.is_primary' => 'nullable|boolean',
            ]);

            $addVendor = DB::table('vendors')->insertGetId([
                'name_vendor' => $data['name_vendor'],
                'created_by' => $request->user()->id_user,
                'status' =>true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($addVendor) {
                if (isset($data['data_vendor']) && is_array($data['data_vendor'])) {
                    foreach ($data['data_vendor'] as $key => $value) {
                        if ($value['is_primary'] == true) {
                            // Set all other data_vendor entries to not primary
                            DB::table('data_vendor')
                                ->where('id_vendor', $addVendor)
                                ->update(['is_primary' => false]);
                        }
                        // Validate each data_vendor entry
                        

                      


                        $dataVendor = [
                            'id_vendor' => $addVendor,
                            'account_number' => $value['account_number'] ?? null,
                            'bank' => $value['bank'] ?? null,
                            'pic' => $value['pic'] ?? null,
                            'is_primary' => isset($value['is_primary']) ? $value['is_primary'] : false,
                            'created_at' => now(),
                            'created_by' => $request->user()->id_user,
                            'updated_at' => now(),
                        ];
                        // Insert data_vendor entry
                        $addDataVendor = DB::table('data_vendor')->insert($dataVendor);
                        if (!$addDataVendor) {
                            throw new Exception('Failed to add vendor detail for entry ' . ($key + 1));
                        }
                    }
                    DB::commit();
                    return ResponseHelper::success('Vendor created successfully.', NULL, 201);
                } else {
                    // Commit the transaction
                    DB::commit();
                    return ResponseHelper::success('Vendor created successfully.', NULL, 201);
                }
            }
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            DB::rollback();
            return ResponseHelper::error($e);
        }
    }

    public function getVendor(Request $request)
    {


        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'vendors.*',
            'users.name as created_by',
        ];
        $vendor = DB::table('vendors')
            ->select($select)
            ->join('users', 'vendors.created_by', '=', 'users.id_user')

            ->where('vendors.name_vendor', 'like', '%' . $search . '%')
            ->orWhere('vendors.id_vendor', 'like', '%' . $search . '%')
            ->orderBy('vendors.created_at', 'desc')
            ->paginate($limit);


        if ($vendor) {
            $dataVendor = DB::table('data_vendor')
                ->whereIn('id_vendor', $vendor->pluck('id_vendor'))
                ->get();
            $vendor->transform(function ($item) use ($dataVendor) {
                $item->data_vendor = $dataVendor->where('id_vendor', $item->id_vendor)
                    ->map(function ($data) {
                        return [
                            'id_datavendor' => $data->id_datavendor,
                            'account_number' => $data->account_number,
                            'bank' => $data->bank,
                            'pic' => $data->pic,
                            'is_primary' => $data->is_primary,
                        ];
                    })
                    ->values() //  reset key
                    ->toArray();
                return $item;
            });

            return ResponseHelper::success('Vendors retrieved successfully.', $vendor, 200);
        } else {
            return ResponseHelper::error(new Exception('No vendors found.'));
        }
    }

    public function getVendorById($id = null)
    {
        if (!$id) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'meta_data' => [
                    'code' => 400,
                    'message' => 'Vendor ID is required.',
                ],
            ], 400);
        }

        $select = [
            'vendors.*',
            'users.name as created_by',
        ];

        $vendor = DB::table('vendors')
            ->select($select)
            ->join('users', 'vendors.created_by', '=', 'users.id_user')
            ->where('vendors.id_vendor', $id)
            ->first();

        if ($vendor) {
            $dataVendor = DB::table('data_vendor')
                
                ->where('id_vendor', $vendor->id_vendor)
                ->whereNull('deleted_at')
                ->get();

            // Format data_vendor according to your desired structure
            $vendor->data_vendor = $dataVendor->map(function ($data) {
                return [
                    'id_datavendor' => $data->id_datavendor,
                    'account_number' => $data->account_number,
                    'bank' => $data->bank,
                    'pic' => $data->pic,
                    'is_primary' => $data->is_primary,
                ];
            })->values()->toArray();

            return ResponseHelper::success('Vendor retrieved successfully.', $vendor, 200);
        } else {
            return ResponseHelper::success('Vendor not found.', null, 404);
        }
    }

    public function deactiveVendor(Request $request)
    {
        // Validate the request data
        $data = $request->validate([
            'id_vendor' => 'required|integer|exists:vendors,id_vendor',
        ]);

        // Find the vendor by ID
        $vendor = DB::table('vendors')->where('id_vendor', $data['id_vendor'])->first();

        $changes = [
            'type' => 'deactivate',
            'old' => [
                'status' => $vendor->status,
            ],
            'new' => [
                'status' => false,
            ],
        ];

        if ($vendor) {
            // Deactivate the vendor
           $updateVendor = DB::table('vendors')->where('id_vendor', $data['id_vendor'])->update(['status' => false]);
            // Log the action
            DB::table('log_vendor')->insert([
                'id_vendor' => $data['id_vendor'],
                'action' => json_encode($changes),
                'id_user' => $request->user()->id_user,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ResponseHelper::success('Vendor deactivated successfully.', NULL, 200);
        } else {
            return ResponseHelper::success('Vendor not found.', NULL, 404);
        }
    }

    public function activateVendor(Request $request)
    {
        // Validate the request data
        $data = $request->validate([
            'id_vendor' => 'required|integer|exists:vendors,id_vendor',
        ]);

        // Find the vendor by ID
        $vendor = DB::table('vendors')->where('id_vendor', $data['id_vendor'])->first();
        $changes = [
            'type' => 'activate',
            'old' => [
                'status' => $vendor->status,
            ],
            'new' => [
                'status' => true,
            ],
        ];

        if ($vendor) {
            // Activate the vendor
            $vendor->status = true;
            $updateVendor = DB::table('vendors')->where('id_vendor', $data['id_vendor'])->update(['status' => true]);

            // Log the action
            DB::table('log_vendor')->insert([
                'id_vendor' => $data['id_vendor'],
                'action' => json_encode($changes),
                'id_user' => $request->user()->id_user,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ResponseHelper::success('Vendor activated successfully.', NULL, 200);
        } else {
            return ResponseHelper::success('Vendor not found.', NULL, 404);
        }
    }

    public function updateDetailVendor(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request data
            $data = $request->validate([
                'id_datavendor' => 'required|integer|exists:data_vendor,id_datavendor',
                'is_primary' => 'required|boolean',
                'account_number' => 'nullable|numeric',
                'bank' => 'nullable|string|max:255',
                'pic' => 'nullable|string|max:100',
            ]);

            $changes = [];
            $dataVendor = DB::table('data_vendor')
                ->where('id_datavendor', $data['id_datavendor'])
                ->first();
            if (!$dataVendor) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'meta_data' => [
                        'code' => 404,
                        'message' => 'Data vendor not found.',
                    ],
                ], 404);
            }
            $oldData = [
                'is_primary' => $dataVendor->is_primary,
                'account_number' => $dataVendor->account_number,
                'bank' => $dataVendor->bank,
                'pic' => $dataVendor->pic,
            ];
            $newData = [
                'is_primary' => $data['is_primary'],
                'account_number' => $data['account_number'],
                'bank' => $data['bank'],
                'pic' => $data['pic'],
            ];
            foreach ($newData as $key => $value) {
                if ($oldData[$key] != $value) {
                    $changes['updated_data_vendor'][] = [
                        'id_datavendor' => $dataVendor->id_datavendor,
                        'field' => $key,
                        'old' => $oldData[$key],
                        'new' => $value,
                    ];
                }
            }
            $updateData = DB::table('data_vendor')
                ->where('id_datavendor', $data['id_datavendor'])
                ->update([
                    'account_number' => $data['account_number'],
                    'bank' => $data['bank'],
                    'pic' => $data['pic'],
                    'updated_at' => now(),
                ]);
            if ($updateData) {

                if ($data['is_primary'] == true) {
                    DB::table('data_vendor')
                        ->where('id_vendor', $dataVendor->id_vendor)
                        ->update(['is_primary' => false]);
                }
                $updatePrimary = DB::table('data_vendor')
                    ->where('id_datavendor', $data['id_datavendor'])
                    ->update(['is_primary' => $data['is_primary']]);
                if ($updatePrimary) {
                    // Log the action
                    if (count($changes) > 0) {
                        $insertLog = DB::table('log_vendor')->insert([
                            'id_vendor' => $dataVendor->id_vendor,
                            'action' => json_encode($changes),
                            'id_user' => $request->user()->id_user,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        if ($insertLog) {
                            DB::commit();
                            return ResponseHelper::success('Vendor detail updated successfully.', NULL, 200);
                        } else {
                            throw new Exception('Failed to log vendor detail update.');
                        }
                    }
                } else {
                    throw new Exception('Failed to update primary status for data vendor ID ' . $data['id_datavendor']);
                }
            } else {
                throw new Exception('Failed to update vendor detail for ID ' . $data['id_datavendor']);
            }
        } catch (Exception $th) {
            DB::rollback();
            return ResponseHelper::error($th);
        }
    }

    public function updateVendor(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validate the request data
            $data = $request->validate([
                'id_vendor' => 'required|integer|exists:vendors,id_vendor',
                'name_vendor' => 'required|string|min:3|unique:vendors,name_vendor,' . $request->input('id_vendor') . ',id_vendor',
                'type' => 'required|in:agent,consignee',
                'data_vendor' => 'nullable|array',
                'data_vendor.*.id_datavendor' => 'nullable|integer|exists:data_vendor,id_datavendor',
                'data_vendor.*.account_number' => 'nullable|numeric',
                'data_vendor.*.bank' => 'nullable|string|max:255',
                'data_vendor.*.pic' => 'nullable|string|max:100',
                'data_vendor.*.is_primary' => 'nullable|boolean',
                'status' => 'required|boolean',
            ]);

            $changes = [];

            // Check if vendor exists
            $vendor = DB::table('vendors')
                ->where('id_vendor', $data['id_vendor'])
                ->first();

            if (!$vendor) {
                throw new Exception('Vendor not found.');
            }

            // Update vendor main data
            $updateVendor = DB::table('vendors')
                ->where('id_vendor', $data['id_vendor'])
                ->update([
                    'name_vendor' => $data['name_vendor'],
                    'updated_at' => now(),
                    'status' => $data['status'] ?? true,
                ]);
                

            if (!$updateVendor) {
                throw new Exception('Failed to update vendor.');
            }

            // Process vendor details
            foreach ($data['data_vendor'] as $key => $value) {
                $dataVendor = DB::table('data_vendor')
                    ->where('id_datavendor', $value['id_datavendor'] ?? null)
                    ->where('id_vendor', $data['id_vendor'])
                    ->first();

                if ($dataVendor) {
                    // Update existing data vendor
                    $dataUpdateInsert = [
                        'account_number' => $value['account_number'] ?? null,
                        'bank' => $value['bank'] ?? null,
                        'pic' => $value['pic'] ?? null,
                        'is_primary' => isset($value['is_primary']) ? $value['is_primary'] : false,
                        'updated_at' => now(),
                    ];

                    // Track changes for logging
                    $oldData = [
                        'is_primary' => $dataVendor->is_primary,
                        'account_number' => $dataVendor->account_number,
                        'bank' => $dataVendor->bank,
                        'pic' => $dataVendor->pic,
                    ];
                    foreach ($oldData as $key => $value) {
                        if ($value != $dataUpdateInsert[$key]) {
                            if (!isset($changes['updated_data_vendor'])) {
                                $changes['updated_data_vendor'] = [];
                            }
                            $changes['updated_data_vendor'][] = [
                                'id_datavendor' => $dataVendor->id_datavendor,
                                'field' => $key,
                                'old' => $value,
                                'new' => $dataUpdateInsert[$key]
                            ];
                        }
                    }
                } else {
                    // Create new data vendor
                    $dataUpdateInsert = [
                        'id_vendor' => $data['id_vendor'],
                        'account_number' => $value['account_number'] ?? null,
                        'bank' => $value['bank'] ?? null,
                        'pic' => $value['pic'] ?? null,
                        'is_primary' => isset($value['is_primary']) ? $value['is_primary'] : false,
                        'created_at' => now(),
                        'created_by' => Auth::id(),
                        'updated_at' => now(),
                    ];

                    // Track new entries for logging
                    if (!isset($changes['new_data_vendor'])) {
                        $changes['new_data_vendor'] = [];
                    }
                    $changes['new_data_vendor'][] = [
                        'id_vendor' => $data['id_vendor'],
                        'data' => $value
                    ];
                }

                // Update or insert data
                DB::table('data_vendor')->updateOrInsert(
                    [
                        'id_datavendor' => $value['id_datavendor'] ?? null,
                        'id_vendor' => $data['id_vendor'],
                        'created_by' => Auth::id()
                    ],
                    $dataUpdateInsert
                );
            }

            $countIsPrimary = DB::table('data_vendor')
                ->where('id_vendor', $data['id_vendor'])
                ->where('is_primary', true)
                ->count();
            if ($countIsPrimary > 1) {
                //hapus salah satu is_primary
                DB::table('data_vendor')
                    ->where('id_vendor', $data['id_vendor'])
                    ->where('is_primary', true)
                    ->limit($countIsPrimary - 1)
                    ->update(['is_primary' => false]);
            }

            // Log the changes
            $log = DB::table('log_vendor')->insert([
                'id_vendor' => $data['id_vendor'],
                'action' => json_encode($changes),
                'id_user' => $request->user()->id_user,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (!$log) {
                throw new Exception('Failed to log vendor update action.');
            }

            // Commit transaction
            DB::commit();

            return ResponseHelper::success('Vendor updated successfully.', NULL, 200);
        } catch (Exception $e) {
            // Rollback transaction on error
            DB::rollback();
            // Return error response
            return ResponseHelper::error($e);
        }
    }
}
