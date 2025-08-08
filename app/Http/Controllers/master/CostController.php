<?php

namespace App\Http\Controllers\master;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;


date_default_timezone_set('Asia/Jakarta');

class CostController extends Controller
{
    public function createCost(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request data
            $request->validate([
                'id_weight_bracket_cost' => 'required|integer|exists:weight_bracket_cost,id_weight_bracket_cost',
                'id_typecost' => 'required|integer|exists:type_cost,id_typecost',
                'id_route' => 'required|integer|exists:route,id_route',
            ]);

            $checkCost = DB::table('cost')
                ->where('id_weight_bracket_cost', $request->id_weight_bracket_cost)
                ->where('id_typecost', $request->id_typecost)
                ->where('id_route', $request->id_route)
                ->first();
            if ($checkCost) {
                throw new Exception('Cost already exists for the given weight bracket, type, and route.');
            }
            $addCost = DB::table('cost')->insert([
                'id_weight_bracket_cost' => $request->id_weight_bracket_cost,
                'id_typecost' => $request->id_typecost,
                'id_route' => $request->id_route,
                'created_by' => $request->created_by,
                'created_at' => now(),
            ]);
            DB::commit();
            return ResponseHelper::success($addCost, 'Cost created successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function getCost(Request $request)
    {
        $limit = $request->input('limit', 10);
        $searchkey = $request->input('searchKey', '');

        $query = DB::table('cost')
            ->join('weight_bracket_costs', 'cost.id_weight_bracket_cost', '=', 'weight_bracket_costs.id_weight_bracket_cost')
            ->join('typecost', 'cost.id_typecost', '=', 'typecost.id_typecost')
            ->join('routes', 'cost.id_route', '=', 'routes.id_route')
            ->join('users', 'cost.created_by', '=', 'users.id_user')
            ->select(
                'cost.*',
                'weight_bracket_costs.weight_range',
                'typecost.type_name',
                'routes.route_name',
                'users.name as created_by'
            )->when($searchkey, function ($query) use ($searchkey) {
                return $query->where('weight_bracket_costs.weight_range', 'like', '%' . $searchkey . '%')
                    ->orWhere('typecost.type_name', 'like', '%' . $searchkey . '%')
                    ->orWhere('routes.route_name', 'like', '%' . $searchkey . '%')
                    ->orWhere('users.name', 'like', '%' . $searchkey . '%');
            });
        $costs = $query->paginate($limit);
        return ResponseHelper::success('Costs retrieved successfully', $costs, 200);
    }

    public function updateCost(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->route('id_cost'); // Assuming the ID is passed in the route
            // Validate the request data
            $request->validate([
                'id_cost' => 'required|integer|exists:cost,id_cost',
                'id_weight_bracket_cost' => 'required|integer|exists:weight_bracket_cost,id_weight_bracket_cost',
                'id_typecost' => 'required|integer|exists:type_cost,id_typecost',
                'id_route' => 'required|integer|exists:route,id_route',
            ]);

            $checkCost = DB::table('cost')
                ->where('id_weight_bracket_cost', $request->id_weight_bracket_cost)
                ->where('id_typecost', $request->id_typecost)
                ->where('id_route', $request->id_route)
                ->where('id_cost', '!=', $id)
                ->first();
            if ($checkCost) {
                throw new Exception('Cost already exists for the given weight bracket, type, and route.');
            }

            $updateCost = DB::table('cost')
                ->where('id_cost', $id)
                ->update([
                    'id_weight_bracket_cost' => $request->id_weight_bracket_cost,
                    'id_typecost' => $request->id_typecost,
                    'id_route' => $request->id_route,
                    'updated_by' => $request->updated_by,
                    'updated_at' => now(),
                ]);
            DB::commit();
            return ResponseHelper::success($updateCost, 'Cost updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteCost(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->route('id'); // Assuming the ID is passed in the route
            // Validate the request data
            $request->validate([
                'id' => 'required|integer|exists:cost,id_cost',
            ]);

            $deleteCost = DB::table('cost')
                ->where('id_cost', $id)
                ->update(['deleted_at' => now(), 'status' => 'inactive']);
            DB::commit();
            return ResponseHelper::success($deleteCost, 'Cost deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function restoreCost(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->route('id'); // Assuming the ID is passed in the route
            // Validate the request data
            $request->validate([
                'id' => 'required|integer|exists:cost,id_cost',
            ]);

            $restoreCost = DB::table('cost')
                ->where('id_cost', $id)
                ->update(['deleted_at' => null, 'status' => 'active']);
            DB::commit();
            return ResponseHelper::success($restoreCost, 'Cost restored successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
