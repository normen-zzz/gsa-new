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
                'id_typeselling' => 'required|integer|exists:type_selling,id_typeselling',
                'id_route' => 'required|integer|exists:route,id_route',
                'created_by' => 'required|integer|exists:users,id',
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
                'created_by' => $request->created_by,
                'created_at' => now(),
            ]);
            DB::commit();
            return ResponseHelper::success($addSelling, 'Selling created successfully');
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
            ->join('type_selling', 'selling.id_typeselling', '=', 'type_selling.id_typeselling')
            ->join('route', 'selling.id_route', '=', 'route.id_route')
            ->join('users', 'selling.created_by', '=', 'users.id')
            ->select(
                'selling.*',
                'weight_bracket_selling.name as weight_bracket_name',
                'type_selling.name as type_selling_name',
                'route.name as route_name',
                'users.name as created_by_name'
            );

        if ($searchkey) {
            $query->where(function ($q) use ($searchkey) {
                $q->where('weight_bracket_selling.name', 'like', '%' . $searchkey . '%')
                    ->orWhere('type_selling.name', 'like', '%' . $searchkey . '%')
                    ->orWhere('route.name', 'like', '%' . $searchkey . '%');
            });
        }

        return ResponseHelper::success($query->paginate($limit), 'Selling data retrieved successfully');
    }

    public function updateSelling(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request data
            $request->validate([
                'id_selling' => 'required|integer|exists:selling,id_selling',
                'id_weight_bracket_selling' => 'required|integer|exists:weight_bracket_selling,id_weight_bracket_selling',
                'id_typeselling' => 'required|integer|exists:type_selling,id_typeselling',
                'id_route' => 'required|integer|exists:route,id_route',
            ]);
            $id = $request->id_selling;

            $checkSelling = DB::table('selling')
                ->where('id_weight_bracket_selling', $request->id_weight_bracket_selling)
                ->where('id_typeselling', $request->id_typeselling)
                ->where('id_route', $request->id_route)
                ->where('id_selling', '!=', $id)
                ->first();
            if ($checkSelling) {
                throw new Exception('Selling already exists for the given weight bracket, type, and route.');
            }
            $update = DB::table('selling')->where('id_selling', $id)->update([
                'id_weight_bracket_selling' => $request->id_weight_bracket_selling,
                'id_typeselling' => $request->id_typeselling,
                'id_route' => $request->id_route,
                'updated_at' => now(),
            ]);
            DB::commit();
            return ResponseHelper::success($update, 'Selling updated successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteSelling($id)
    {
        DB::beginTransaction();
        try {
            $selling = DB::table('selling')->where('id_selling', $id)->first();
            if (!$selling) {
                throw new Exception('Selling not found');
            }

            $delete = DB::table('selling')->where('id_selling', $id)->update([
                'deleted_at' => now(),
                'deleted_by' => Auth::id(),
            ]);
            DB::commit();
            return ResponseHelper::success(null, 'Selling deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function restoreSelling($id)
    {
        DB::beginTransaction();
        try {
            $selling = DB::table('selling')->where('id_selling', $id)->first();
            if (!$selling) {
                throw new Exception('Selling not found');
            }

            $restore = DB::table('selling')->where('id_selling', $id)->update([
                'deleted_at' => null,
                'deleted_by' => null,
                'status' => 'active',
            ]);
            DB::commit();
            return ResponseHelper::success(null, 'Selling restored successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
