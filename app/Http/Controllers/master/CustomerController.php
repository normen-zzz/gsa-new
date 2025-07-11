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
            ]);

            // Create a new customer
            $customer = new CustomerModel();
            $customer->name_customer = $data['name_customer'];
            $customer->status = true; // Default status, can be changed as needed
            $customer->created_by = $request->user()->id_user; // Assuming the user is authenticated and has an ID


            if ($customer->save()) {
                $insertLog = DB::table('log_customer')->insert([
                    'id_customer' => $customer->id_customer,
                    'action' => 'create ' . $customer->name_customer,
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
                'data' => $customer,
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
        $customer = DB::table('customers')
            ->select('customers.id_customer', 'customers.name_customer', 'customers.status', 'customers.created_at', 'users.name as created_by')
            ->join('users', 'customers.created_by', '=', 'users.id_user')
            ->where('customers.name_customer', 'like', '%' . $search . '%')
            ->orWhere('customers.id_customer', 'like', '%' . $search . '%')
            ->orderBy('customers.created_at', 'desc')
            ->paginate($limit);


        if ($customer) {
            $id_customer = $customer->pluck('id_customer')->toArray();
            $detail = DB::table('customer_details')
                ->whereIn('id_customer', $id_customer)
                ->get();
            // add $detail on $customer
            $customer->getCollection()->transform(function ($item) use ($detail) {
                $item->details = $detail->where('id_customer', $item->id_customer);
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

    public function addDetailCustomer(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request data
            $data = $request->validate([
                'id_customer' => 'required|integer|exists:customers,id_customer',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'required|string',
                'tax_id' => 'nullable|string|max:50',
                'pic' => 'nullable|string|max:100',
            ]);

            // Create a new customer detail
            $customerDetail = DB::table('customer_details')->insert([
                'id_customer' => $data['id_customer'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'tax_id' => $data['tax_id'],
                'pic' => $data['pic'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $id_customerdetail = DB::getPdo()->lastInsertId();

            if (!$customerDetail) {
                throw new Exception('Failed to add customer detail.');
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'code' => 201,
                'data' => [
                    'id_customerdetail' => $id_customerdetail,
                    'id_customer' => $data['id_customer'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'tax_id' => $data['tax_id'],
                    'pic' => $data['pic'],
                ],
                'meta_data' => [
                    'code' => 201,
                    'message' => 'Customer detail added successfully.',
                ],
            ], 201);
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

    public function updateDetailCustomer(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request data
            $data = $request->validate([
                'id_customerdetail' => 'required|integer|exists:customer_details,id_customerdetail',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'required|string',
                'tax_id' => 'nullable|string|max:50',
                'pic' => 'nullable|string|max:100',
            ]);

            // Update the customer detail
            $detailCustomer = DB::table('customer_details')
                ->where('id_customerdetail', $data['id_customerdetail'])
                ->first();
            // cek apa saja perubahannya 
            if (!$detailCustomer) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'meta_data' => [
                        'code' => 404,
                        'message' => 'Customer detail not found.',
                    ],
                ], 404);
            } else {
                // cek apa saja perubahannya
                $changes = [];
                foreach ($data as $key => $value) {
                    if ($detailCustomer->$key !== $value) {
                        $changes[$key] = [
                            'old' => $detailCustomer->$key,
                            'new' => $value,
                        ];
                    }
                }
                $updated = DB::table('customer_details')
                    ->where('id_customerdetail', $data['id_customerdetail'])
                    ->update([
                        'email' => $data['email'],
                        'phone' => $data['phone'],
                        'address' => $data['address'],
                        'tax_id' => $data['tax_id'],
                        'pic' => $data['pic'],
                        'updated_at' => now(),
                    ]);
            }


            if ($updated) {
                // Log the update action
                DB::table('log_customer')->insert([
                    'id_customer' => DB::table('customer_details')
                        ->where('id_customerdetail', $data['id_customerdetail'])
                        ->value('id_customer'),
                    'id_customerdetail' => $data['id_customerdetail'],
                    'action' => 'update detail for customer ID ' . $data['id_customerdetail'] . ' with changes: ' . json_encode($changes),
                    'id_user' => $request->user()->id_user,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                throw new Exception('Failed to update customer detail.');
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => [
                    'id_customerdetail' => $data['id_customerdetail'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'tax_id' => $data['tax_id'],
                    'pic' => $data['pic'],
                ],
                'meta_data' => [
                    'code' => 200,
                    'message' => 'Customer detail updated successfully.',
                ],
            ], 200);
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
