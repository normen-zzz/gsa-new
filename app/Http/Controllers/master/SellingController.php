<?php

namespace App\Http\Controllers\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

date_default_timezone_set('Asia/Jakarta');

use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Auth;

class SellingController extends Controller
{
    public function createSelling(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request data
            $request->validate([
                'id_weight_bracket_selling' => 'required|integer|exists:weight_bracket_selling,id_weight_bracket_selling',
                'id_typeselling' => 'required|integer|exists:typeselling,id_typeselling',
                'id_route' => 'required|integer|exists:routes,id_route',
                'selling_value' => 'required|numeric|min:0',
                'charge_by' => 'required|in:chargeable_weight,gross_weight,awb',
            ]);

            $checkSelling = DB::table('selling')
                ->where('id_weight_bracket_selling', $request->id_weight_bracket_selling)
                ->where('id_typeselling', $request->id_typeselling)
                ->where('id_route', $request->id_route)
                ->first();
            if ($checkSelling) {
                throw new Exception('Selling already exists for the given weight bracket, type, and route.');
            }

            $addSelling = DB::table('selling')->insert([
                'id_weight_bracket_selling' => $request->id_weight_bracket_selling,
                'id_typeselling' => $request->id_typeselling,
                'id_route' => $request->id_route,
                'selling_value' => $request->selling_value,
                'charge_by' => $request->charge_by,
                'created_by' => Auth::id(), // Assuming the user ID is obtained from the authenticated user
                'created_at' => now(),
            ]);
            DB::commit();
            return ResponseHelper::success('Selling created successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function getSelling(Request $request)
    {
        $limit = $request->input('limit', 10);
        $searchkey = $request->input('searchKey', '');

        $query = DB::table('selling')
            ->join('weight_bracket_selling', 'selling.id_weight_bracket_selling', '=', 'weight_bracket_selling.id_weight_bracket_selling')
            ->join('typeselling', 'selling.id_typeselling', '=', 'typeselling.id_typeselling')
            ->join('routes', 'selling.id_route', '=', 'routes.id_route')
            ->join('airlines', 'routes.airline', '=', 'airlines.id_airline')
            ->join('airports as pol', 'routes.pol', '=', 'pol.id_airport')
            ->join('airports as pod', 'routes.pod', '=', 'pod.id_airport')
            ->join('users', 'selling.created_by', '=', 'users.id_user')
            ->select(
                'selling.id_selling',
                'selling.id_weight_bracket_selling',
                'weight_bracket_selling.min_weight',
                'selling.id_typeselling',
                'typeselling.name as type_cost_name',
                'selling.id_route',
                'airlines.name as airline_name',
                'pol.name_airport as pol_name',
                'pod.name_airport as pod_name',
                'selling.selling_value',
                'selling.charge_by',
                'selling.created_at',
                'selling.updated_at',
                'selling.deleted_at',
                'selling.created_by',
               'users.name as created_by_name',
                'selling.updated_by',
                'selling.deleted_by',
               
            )->when($searchkey, function ($query) use ($searchkey) {
                return $query->where('weight_bracket_selling.min_weight', 'like', '%' . $searchkey . '%')
                    ->orWhere('typeselling.name', 'like', '%' . $searchkey . '%')
                    ->orWhere('airlines.name', 'like', '%' . $searchkey . '%')
                    ->orWhere('pol.name_airport', 'like', '%' . $searchkey . '%')
                    ->orWhere('pod.name_airport', 'like', '%' . $searchkey . '%')
                    ->orWhere('users.name', 'like', '%' . $searchkey . '%');
            });
        $sellings = $query->paginate($limit);
        return ResponseHelper::success('Sellings retrieved successfully', $sellings, 200);
    }

    public function updateSelling(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_selling'); // Assuming the ID is passed in the route
            // Validate the request data
            $selling = DB::table('selling')->where('id_selling', $id)->first();
            $request->validate([
                'id_selling' => 'required|integer|exists:selling,id_selling',
                'id_weight_bracket_selling' => 'required|integer|exists:weight_bracket_selling,id_weight_bracket_selling',
                'id_typeselling' => 'required|integer|exists:typeselling,id_typeselling',
                'id_route' => 'required|integer|exists:routes,id_route',
                'selling_value' => 'required|numeric|min:0',
                'charge_by' => 'required|in:chargeable_weight,gross_weight,awb',
            ]);

            $checkSelling = DB::table('selling')
                ->where('id_weight_bracket_selling', $request->id_weight_bracket_selling)
                ->where('id_typeselling', $request->id_typeselling)
                ->where('id_route', $request->id_route)
                ->where('selling_value', $request->selling_value)
                ->where('id_selling', '!=', $id)
                ->first();
            if ($checkSelling) {
                throw new Exception('Selling already exists for the given weight bracket, type, and route.');
            }

            $updateSelling = DB::table('selling')
                ->where('id_selling', $id)
                ->update([
                    'id_weight_bracket_selling' => $request->id_weight_bracket_selling,
                    'id_typeselling' => $request->id_typeselling,
                    'id_route' => $request->id_route,
                    'selling_value' => $request->selling_value,
                    'charge_by' => $request->charge_by,
                    'updated_by' => Auth::id(), // Assuming the user ID is obtained from the authenticated user
                    'updated_at' => now(),
                ]);
            $changes = [];
            foreach ($request->all() as $key => $value) {
                if ($selling->$key != $value) {
                    $changes[$key] = [
                        'type' => 'update',
                        'old' => $selling->$key,
                        'new' => $value
                    ];
                }
            }
            if (!empty($changes)) {
                DB::table('log_selling')->insert([
                    'id_selling' => $id,
                    'action' => json_encode($changes),
                    'id_user' => $request->user()->id_user,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::commit();
            return ResponseHelper::success('Selling updated successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteSelling(Request $request)
    {

        DB::beginTransaction();
        try {
            $id = $request->input('id_selling'); // Assuming the ID is passed in the request
            $selling = DB::table('selling')->where('id_selling', $id)->first();
            if (!$selling) {
                throw new Exception('Selling not found');
            }

            $delete = DB::table('selling')->where('id_selling', $id)->update([
                'deleted_at' => now(),
                'deleted_by' => Auth::id(),
                'status' => 'inactive',
            ]);
            $changes = [
                'type' => 'delete',
                'old' => [
                    'status' => $selling->status,
                ],
                'new' => [
                    'status' => 'inactive',
                ],
            ];
            DB::table('log_selling')->insert([
                'id_selling' => $id,
                'action' => json_encode($changes),
                'id_user' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
            return ResponseHelper::success('Selling deleted successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function restoreSelling(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_selling'); // Assuming the ID is passed in the request
            $selling = DB::table('selling')->where('id_selling', $id)->first();
            if (!$selling) {
                throw new Exception('Selling not found');
            }

            $restore = DB::table('selling')->where('id_selling', $id)->update([
                'deleted_at' => null,
                'deleted_by' => null,
                'updated_by' => Auth::id(),
                'updated_at' => now(),
                'status' => 'active',
            ]);
            $changes = [
                'type' => 'restore',
                'old' => [
                    'status' => $selling->status,
                ],
                'new' => [
                    'status' => 'active',
                ],
            ];
            DB::table('log_selling')->insert([
                'id_selling' => $id,
                'action' => json_encode($changes),
                'id_user' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
            return ResponseHelper::success('Selling restored successfully', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
