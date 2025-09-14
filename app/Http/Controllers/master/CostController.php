<?php

namespace App\Http\Controllers\master;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;

date_default_timezone_set('Asia/Jakarta');

class CostController extends Controller
{
    public function createCost(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request data
            $request->validate([
                'id_weight_bracket_cost' => 'required|integer|exists:weight_bracket_costs,id_weight_bracket_cost',
                'id_typecost' => 'required|integer|exists:typecost,id_typecost',
                'id_route' => 'required|integer|exists:routes,id_route',
                'cost_value' => 'required|numeric|min:0',
                'charge_by' => 'required|in:chargeable_weight,gross_weight,awb',
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
                'cost_value' => $request->cost_value,
                'charge_by' => $request->charge_by,
                'created_by' => Auth::id(), // Assuming the user ID is obtained from the authenticated user
                'created_at' => now(),

            ]);
            DB::commit();
            return ResponseHelper::success('Cost created successfully.', null, 200);
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
            ->join('airlines', 'routes.airline', '=', 'airlines.id_airline')
            ->join('airports as pol', 'routes.pol', '=', 'pol.id_airport')
            ->join('airports as pod', 'routes.pod', '=', 'pod.id_airport')
            ->join('users', 'cost.created_by', '=', 'users.id_user')
            ->select(
                'cost.id_cost',
                'cost.id_weight_bracket_cost',
                'weight_bracket_costs.min_weight',
                'cost.id_typecost',
                'typecost.name as type_cost_name',
                'typecost.initials as type_cost_initial',
                'cost.id_route',
                'cost.cost_value',
                'cost.charge_by',
                'airlines.name as airline_name',
                'pol.name_airport as pol_name',
                'pod.name_airport as pod_name',
                'users.name as created_by',
                'cost.created_at',
                'cost.updated_at',
                'cost.deleted_at',
                'cost.created_by',
                'cost.updated_by',
                'cost.deleted_by'
            )->when($searchkey, function ($query) use ($searchkey) {
                return $query->where('weight_bracket_costs.min_weight', 'like', '%' . $searchkey . '%')
                    ->orWhere('typecost.name', 'like', '%' . $searchkey . '%')
                    ->orWhere('airlines.name', 'like', '%' . $searchkey . '%')
                    ->orWhere('pol.name', 'like', '%' . $searchkey . '%')
                    ->orWhere('pod.name', 'like', '%' . $searchkey . '%')
                    ->orWhere('users.name', 'like', '%' . $searchkey . '%');
            });
        $costs = $query->paginate($limit);
        return ResponseHelper::success('Costs retrieved successfully', $costs, 200);
    }

    public function updateCost(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_cost'); // Assuming the ID is passed in the route
            // Validate the request data
            $cost = DB::table('cost')->where('id_cost', $id)->first();
            $request->validate([
                'id_cost' => 'required|integer|exists:cost,id_cost',
                'id_weight_bracket_cost' => 'required|integer|exists:weight_bracket_costs,id_weight_bracket_cost',
                'id_typecost' => 'required|integer|exists:typecost,id_typecost',
                'id_route' => 'required|integer|exists:routes,id_route',
                'cost_value' => 'required|numeric|min:0',
                'charge_by' => 'required|in:chargeable_weight,gross_weight,awb',
            ]);

            $checkCost = DB::table('cost')
                ->where('id_weight_bracket_cost', $request->id_weight_bracket_cost)
                ->where('id_typecost', $request->id_typecost)
                ->where('id_route', $request->id_route)
                ->where('cost_value', $request->cost_value)
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
                    'cost_value' => $request->cost_value,
                    'charge_by' => $request->charge_by,
                    'updated_by' => $request->updated_by,
                    'updated_at' => now(),
                ]);
            $changes = [];
            foreach ($request->all() as $key => $value) {
                if ($cost->$key != $value) {
                    $changes[$key] = [
                        'type' => 'update',
                        'old' => $cost->$key,
                        'new' => $value
                    ];
                }
            }
            if (!empty($changes)) {
                DB::table('log_cost')->insert([
                    'id_cost' => $id,
                    'action' => json_encode($changes),
                    'id_user' => $request->user()->id_user,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::commit();
            return ResponseHelper::success('Cost updated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteCost(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_cost'); // Assuming the ID is passed in the route
            // Validate the request data
            $request->validate([
                'id_cost' => 'required|integer|exists:cost,id_cost',
            ]);

            $deleteCost = DB::table('cost')
                ->where('id_cost', $id)
                ->update(['deleted_at' => now(), 'status' => 'inactive']);
            $changes = [
                'type' => 'delete',
                'old' => [
                    'status' => 'active',
                ],
                'new' => [
                    'status' => 'inactive',
                ],
            ];
            DB::table('log_cost')->insert([
                'id_cost' => $id,
                'action' => json_encode($changes),
                'id_user' => $request->user()->id_user,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
            return ResponseHelper::success('Cost deleted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function restoreCost(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_cost'); // Assuming the ID is passed in the route
            // Validate the request data
            $request->validate([
                'id_cost' => 'required|integer|exists:cost,id_cost',
            ]);

            $restoreCost = DB::table('cost')
                ->where('id_cost', $id)
                ->update(['deleted_at' => null, 'status' => 'active']);
            $changes = [
                'type' => 'restore',
                'old' => [
                    'status' => 'inactive',
                ],
                'new' => [
                    'status' => 'active',
                ],
            ];
            DB::table('log_cost')->insert([
                'id_cost' => $id,
                'action' => json_encode($changes),
                'id_user' => $request->user()->id_user,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
            return ResponseHelper::success('Cost restored successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
