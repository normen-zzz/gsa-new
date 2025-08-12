<?php

namespace App\Http\Controllers\flow\salesorder;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


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
                'attachments' => 'nullable|array',
                'attachments.*.url' => 'required|url|max:2048',
                'attachments.*.name' => 'required|string|max:255',
                'selling' => 'required|array',
                'selling.*.id_typeselling' => 'required|integer|exists:typeselling,id_typeselling',
                'selling.*.selling_value' => 'required|numeric|min:0',
                'selling.*.description' => 'nullable|string|max:255'
            ]);

            // Process the sales order creation
            // ...

            DB::commit();
            return response()->json(['message' => 'Sales order created successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create sales order'], 500);
        }
    }
    
}
