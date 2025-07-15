<?php

namespace App\Http\Controllers\flow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShippingInstructionController extends Controller
{
    public function getShippingInstructions(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        
        $query = DB::table('shipping_instructions')
            ->select('shipping_instructions.id', 'shipping_instructions.description', 'shipping_instructions.status', 'users.name as created_by', 'shipping_instructions.created_at')
            ->join('users', 'shipping_instructions.created_by', '=', 'users.id_user')
            ->where('shipping_instructions.description', 'like', '%' . $search . '%')
            ->orderBy('shipping_instructions.created_at', 'desc');

        $instructions = $query->paginate($limit);

        return response()->json([
            'status' => 'success',
            'data' => $instructions,
            'meta_data' => [
                'code' => 200,
                'message' => 'Shipping instructions retrieved successfully.',
            ],
        ]);
    }
}
