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
                'data_customer' => 'nullable|array',
                'data_customer.*.email' => 'nullable|email|max:255',
                'data_customer.*.phone' => 'nullable|string|max:20',
                'data_customer.*.address' => 'required|string',
                'data_customer.*.tax_id' => 'nullable|string|max:50',
                'data_customer.*.pic' => 'nullable|string|max:100',
            ]);

            // Create a new customer
            $customer = new CustomerModel();
            $customer->name_customer = $data['name_customer'];
            $customer->status = true; // Default status, can be changed as needed
            $customer->created_by = $request->user()->id_user; // Assuming the user is authenticated and has an ID
            $customer->type = $data['type'];
            $customer->data_customer = json_encode($data['data_customer']); // Store data_customer as JSON


            if ($customer->save()) {
                $insertLog = DB::table('log_customer')->insert([
                    'id_customer' => $customer->id_customer,
                    'action' => 'Customer created: ' . $customer->name_customer. ' with type ' . $customer->type. ' and data: ' . json_encode($data['data_customer']),
                    'id_user' => $request->user()->id_user,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                if (!$insertLog) {
                    throw new Exception('Failed to log customer creation.');
                }
            } else {
                throw new Exception('Failed to save customer.');
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
        } catch (ValidationException $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'meta_data' => [
                    'code' => 400,
                    'errors' => $e->errors(),
                ],
            ], 200);
        } catch (Exception $e) {
            // Something went wrong, rollback the transaction
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'meta_data' => [
                    'code' => 500,
                    'message' => 'Failed to create customer: ' . $e->getMessage(),
                ],
            ], 500);
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
            $customer->getCollection()->transform(function ($item) {
                $item->data_customer = json_decode($item->data_customer);
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
        // Validate the ID
        if (is_null($id) || !is_numeric($id)) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'meta_data' => [
                    'code' => 400,
                    'message' => 'Invalid customer ID.',
                ],
            ], 400);
        }

        // Retrieve the customer by ID
        $customer = CustomerModel::find($id);
        $customer->data_customer = json_decode($customer->data_customer);

        if ($customer) {
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
                'id_customer' => 'required|integer|exists:customers,id_customer',
                'data_customer' => 'required|array',
                'data_customer.*.email' => 'nullable|email|max:255',
                'data_customer.*.phone' => 'nullable|string|max:20',
                'data_customer.*.address' => 'required|string',
                'data_customer.*.tax_id' => 'nullable|string|max:50',
                'data_customer.*.pic' => 'nullable|string|max:100',
            ]);

            $changes = [];
            $customer = CustomerModel::find($data['id_customer']);
            $oldDetails = json_decode($customer->data_customer, true);
            foreach ($data['data_customer'] as $key => $value) {
                if (isset($oldDetails[$key]) && $oldDetails[$key] != $value) {
                    $changes[$key] = [
                        'old' => $oldDetails[$key],
                        'new' => $value
                    ];
                }
            }

            $updateDataCustomer = DB::table('customers')
                ->where('id_customer', $data['id_customer'])
                ->update([
                    'data_customer' => json_encode($data['data_customer']),
                    'updated_at' => now(),
                ]);
            if ($updateDataCustomer) {
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
