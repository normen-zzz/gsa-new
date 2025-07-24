<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\master\CustomerModel;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    public function createCustomer(Request $request)
    {
        try {
            // Start a database transaction
            DB::beginTransaction();

            // Validate the request data
            $data = $request->validate([
                'name_customer' => 'required|string|min:3|unique:customers,name_customer',
                'type' => 'required|in:agent,consignee',
                'status' => 'nullable|boolean',
                'data_customer' => 'nullable|array',
                'data_customer.*.email' => 'nullable|email|max:255',
                'data_customer.*.phone' => 'nullable|string|max:20',
                'data_customer.*.address' => 'required|string',
                'data_customer.*.tax_id' => 'nullable|string|max:50',
                'data_customer.*.pic' => 'nullable|string|max:100',
                'data_customer.*.is_primary' => 'nullable|boolean',
            ]);

            $addCustomer = DB::table('customers')->insertGetId([
                'name_customer' => $data['name_customer'],
                'type' => $data['type'],
                'created_by' => $request->user()->id_user,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($addCustomer) {
                if (isset($data['data_customer']) && is_array($data['data_customer'])) {
                    foreach ($data['data_customer'] as $key => $value) {
                        if($value['is_primary'] == true) {
                            // Set all other data_customer entries to not primary
                            DB::table('data_customer')
                                ->where('id_customer', $addCustomer)
                                ->update(['is_primary' => false]);
                        }
                        // Validate each data_customer entry
                        $request->validate([
                            'data_customer.' . $key . '.email' => 'nullable|email|max:255',
                            'data_customer.' . $key . '.phone' => 'nullable|string|max:20',
                            'data_customer.' . $key . '.address' => 'required|string',
                            'data_customer.' . $key . '.tax_id' => 'nullable|string|max:50',
                            'data_customer.' . $key . '.pic' => 'nullable|string|max:100',
                        ]);
                        // Prepare data for insertion
                        $dataCustomer = [
                            'id_customer' => $addCustomer,
                            'data' => json_encode($value),
                            'is_primary' => isset($value['is_primary']) ? $value['is_primary'] : false,
                            'created_at' => now(),
                            'created_by' => $request->user()->id_user,
                            'updated_at' => now(),
                        ];
                        // Insert data_customer entry
                        $addDataCustomer = DB::table('data_customer')->insert($dataCustomer);
                        if (!$addDataCustomer) {
                            throw new Exception('Failed to add customer detail for entry ' . ($key + 1));
                        }
                    }
                    DB::commit();
                    return response()->json([
                        'status' => 'success',
                        'code' => 201,
                        'meta_data' => [
                            'code' => 201,
                            'message' => 'Customer created successfully.',
                        ],
                    ], 201);
                } else {
                    // Commit the transaction
                    DB::commit();
                    return response()->json([
                        'status' => 'success',
                        'code' => 201,
                        'meta_data' => [
                            'code' => 201,
                            'message' => 'Customer created successfully without details.',
                        ],
                    ], 201);
                }
            }
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'meta_data' => [
                    'code' => 500,
                    'message' => 'Failed to create customer: ' . $e->getMessage(),
                ],
            ], 500);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'meta_data' => [
                    'code' => 422,
                    'message' => $e->validator->errors()->first(),
                ],
            ], 422);
        }
    }

    public function getCustomer(Request $request)
    {


        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'customers.*',
            'users.name as created_by',


        ];
        $customer = DB::table('customers')
            ->select($select)
            ->join('users', 'customers.created_by', '=', 'users.id_user')

            ->where('customers.name_customer', 'like', '%' . $search . '%')
            ->orWhere('customers.id_customer', 'like', '%' . $search . '%')
            ->orderBy('customers.created_at', 'desc')
            ->paginate($limit);


        if ($customer) {
            $dataCustomer = DB::table('data_customer')
                ->select('id_datacustomer', 'id_customer', 'data', 'is_primary')
                ->whereIn('id_customer', $customer->pluck('id_customer'))
                ->get();
            $customer->transform(function ($item) use ($dataCustomer) {
                $item->data_customer = $dataCustomer->where('id_customer', $item->id_customer)
                    ->map(function ($data) {
                        return [
                            'id_datacustomer' => $data->id_datacustomer,
                            'data' => json_decode($data->data, true),
                            'is_primary' => $data->is_primary,
                        ];
                    })
                    ->values() // ✅ reset key
                    ->toArray();
                return $item;
            });

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => $customer,
                'meta_data' => [
                    'code' => 200,
                    'message' => 'Customer retrieved successfully.',
                ],
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'meta_data' => [
                    'code' => 404,
                    'message' => 'Customer not found.',
                ],
            ], 404);
        }
    }

    public function getCustomerById($id = null)
    {
        if (!$id) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'meta_data' => [
                    'code' => 400,
                    'message' => 'Customer ID is required.',
                ],
            ], 400);
        }

        $select = [
            'customers.*',
            'users.name as created_by',
        ];
        $customer = DB::table('customers')
            ->select($select)
            ->join('users', 'customers.created_by', '=', 'users.id_user')
            ->where('customers.id_customer', $id)->first();
        if ($customer) {
            $dataCustomer = DB::table('data_customer')
                ->select('id_datacustomer', 'data', 'is_primary')
                ->where('id_customer', $customer->id_customer)
                ->get();
            $customer->data_customer = $dataCustomer->map(function ($item) {
                return [
                    'id_datacustomer' => $item->id_datacustomer,
                    'data' => json_decode($item->data, true),
                ];
            })->toArray();
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => $customer,
                'meta_data' => [
                    'code' => 200,
                    'message' => 'Customer retrieved successfully.',
                ],
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'meta_data' => [
                    'code' => 404,
                    'message' => 'Customer not found.',
                ],
            ], 404);
        }
    }

    public function deactiveCustomer(Request $request)
    {
        // Validate the request data
        $data = $request->validate([
            'id_customer' => 'required|integer|exists:customers,id_customer',
        ]);

        // Find the customer by ID
        $customer = CustomerModel::find($data['id_customer']);

        if ($customer) {
            // Deactivate the customer
            $customer->status = false;

            $customer->save();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'meta_data' => [
                    'code' => 200,
                    'data' => $customer,
                    'message' => 'Customer deactivated successfully.',
                ],
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'meta_data' => [
                    'code' => 404,
                    'message' => 'Customer not found.',
                ],
            ], 404);
        }
    }

    public function activateCustomer(Request $request)
    {
        // Validate the request data
        $data = $request->validate([
            'id_customer' => 'required|integer|exists:customers,id_customer',
        ]);

        // Find the customer by ID
        $customer = CustomerModel::find($data['id_customer']);

        if ($customer) {
            // Activate the customer
            $customer->status = true;
            $customer->save();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => $customer,
                'meta_data' => [
                    'code' => 200,
                    'message' => 'Customer activated successfully.',
                ],
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'meta_data' => [
                    'code' => 404,
                    'message' => 'Customer not found.',
                ],
            ], 404);
        }
    }

    public function updateDetailCustomer(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request data
            $data = $request->validate([
                'id_datacustomer' => 'required|integer|exists:data_customer,id_datacustomer',
                'is_primary' => 'required|boolean',
                'data.email' => 'nullable|email|max:255',
                'data.phone' => 'nullable|string|max:20',
                'data.address' => 'required|string',
                'data.tax_id' => 'nullable|string|max:50',
                'data.pic' => 'nullable|string|max:100',
            ]);

            $changes = [];
            $dataCustomer = DB::table('data_customer')
                ->where('id_datacustomer', $data['id_datacustomer'])
                ->first();
            if (!$dataCustomer) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'meta_data' => [
                        'code' => 404,
                        'message' => 'Data customer not found.',
                    ],
                ], 404);
            }
            $oldData = json_decode($dataCustomer->data, true);
            foreach ($data['data'] as $key => $value) {
                if (isset($oldData[$key]) && $oldData[$key] != $value) {
                    $changes[$key] = [
                        'id_datacustomer' => $data['id_datacustomer'],
                        'old' => $oldData[$key],
                        'new' => $value
                    ];
                }
            }
            $updateData = DB::table('data_customer')
                ->where('id_datacustomer', $data['id_datacustomer'])
                ->update([
                    'data' => json_encode($data['data']),
                    'updated_at' => now(),
                ]);
            if ($updateData) {

                if ($data['is_primary'] == true) {
                    DB::table('data_customer')
                        ->where('id_customer', $dataCustomer->id_customer)
                        ->update(['is_primary' => false]);
                }
                $updatePrimary = DB::table('data_customer')
                    ->where('id_datacustomer', $data['id_datacustomer'])
                    ->update(['is_primary' => $data['is_primary']]);
                if ($updatePrimary) {
                    // Log the action
                    if (count($changes) > 0) {
                        $insertLog = DB::table('log_customer')->insert([
                            'id_customer' => $dataCustomer->id_customer,
                            'action' => json_encode($changes),
                            'id_user' => $request->user()->id_user,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        if ($insertLog) {
                            DB::commit();
                            return response()->json([
                                'status' => 'success',
                                'code' => 200,
                                'meta_data' => [
                                    'code' => 200,
                                    'message' => 'Customer details updated successfully.',
                                ],
                            ], 200);
                        } else {
                            throw new Exception('Failed to log customer detail update.');
                        }
                    }
                } else {
                    throw new Exception('Failed to update primary status for data customer ID ' . $data['id_datacustomer']);
                }
            } else {
                throw new Exception('Failed to update customer detail for ID ' . $data['id_datacustomer']);
            }
        } catch (Exception $th) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'meta_data' => [
                    'code' => 500,
                    'message' => 'Failed to add customer detail: ' . $th->getMessage(),
                ],
            ], 500);
        }
    }

    public function updateCustomer(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request data
            $data = $request->validate([

                'id_customer' => 'required|integer|exists:customers,id_customer',
                'name_customer' => 'required|string|min:3|unique:customers,name_customer,' . $request->input('id_customer') . ',id_customer',
                'type' => 'required|in:agent,consignee',
                'data_customer' => 'required|array',
                'data_customer.*.email' => 'nullable|email|max:255',
                'data_customer.*.phone' => 'nullable|string|max:20',
                'data_customer.*.address' => 'required|string',
                'data_customer.*.tax_id' => 'nullable|string|max:50',
                'data_customer.*.pic' => 'nullable|string|max:100',
            ]);

            $customer = CustomerModel::find($data['id_customer']);
            // cek perbedaan antara $data['data_customer'] dan data lama
            $oldDetails = json_decode($customer->data_customer, true);
            $changes = [];
            foreach ($data['data_customer'] as $key => $value) {
                if (isset($oldDetails[$key]) && $oldDetails[$key] != $value) {
                    $changes[$key] = [
                        'old' => $oldDetails[$key],
                        'new' => $value
                    ];
                }
            }

            if ($customer->name_customer != $data['name_customer']) {
                $changes['name_customer'] = [
                    'old' => $customer->name_customer,
                    'new' => $data['name_customer']
                ];
            }
            if ($customer->type != $data['type']) {
                $changes['type'] = [
                    'old' => $customer->type,
                    'new' => $data['type']
                ];
            }

            $updateCustomer = DB::table('customers')
                ->where('id_customer', $data['id_customer'])
                ->update([
                    'name_customer' => $data['name_customer'],
                    'type' => $data['type'],
                    'data_customer' => json_encode($data['data_customer']),
                    'updated_at' => now(),
                ]);

            if ($updateCustomer) {
                // Log the action
                if (count($changes) > 0) {
                    $insertLog = DB::table('log_customer')->insert([
                        'id_customer' => $data['id_customer'],
                        'action' => json_encode($changes),
                        'id_user' => $request->user()->id_user,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    if ($insertLog) {
                        DB::commit();
                        return response()->json([
                            'status' => 'success',
                            'code' => 200,
                            'meta_data' => [
                                'code' => 200,
                                'message' => 'Customer detail updated successfully.',
                            ],
                        ], 200);
                    } else {
                        throw new Exception('Failed to log customer detail update.');
                    }
                } else {
                    DB::commit();
                    return response()->json([
                        'status' => 'success',
                        'code' => 200,
                        'meta_data' => [
                            'code' => 200,
                            'message' => 'Customer detail updated successfully with no changes.',
                        ],
                    ], 200);
                }
            } else {
                throw new Exception('Failed to update customer detail for ID ' . $data['id_customer']);
            }
        } catch (Exception $th) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'meta_data' => [
                    'code' => 500,
                    'message' => $th->getMessage(),
                ],
            ], 500);
        }
    }
}
