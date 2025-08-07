<?php

namespace App\Http\Controllers\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Auth;    

date_default_timezone_set('Asia/Jakarta');

class WeightbracketController extends Controller
{

    public function getWeightBracketCost(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'weight_bracket_costs.id_weight_bracket_cost',
            'weight_bracket_costs.min_weight',
            'weight_bracket_costs.created_at',
            'weight_bracket_costs.updated_at',
            'weight_bracket_costs.deleted_at',
            'weight_bracket_costs.created_by',
            'weight_bracket_costs.updated_by',
            'users.name as created_by_name'
        ];

        $brackets = DB::table('weight_bracket_costs')
            ->select($select)
            ->join('users', 'weight_bracket_costs.created_by', '=', 'users.id_user')
            ->when($search, function ($query) use ($search) {
                return $query->where('weight_bracket_costs.min_weight', 'like', '%' . $search . '%');
            })
            ->orderBy('weight_bracket_costs.id_weight_bracket_cost', 'asc')
            ->paginate($limit);

        return ResponseHelper::success('Weight brackets retrieved successfully.', $brackets, 200);
    }
    public function createWeightBracketCost(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'min_weight' => 'required|numeric|min:0',
            ]);

            $weightBracket = DB::table('weight_bracket_costs')->insert([
                'min_weight' => $request->min_weight,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            DB::commit();
            return ResponseHelper::success($weightBracket, 'Weight bracket created successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
        
    }

    public function updateWeightBracketCost(Request $request)
    {
        DB::beginTransaction();
        try {
            
            $request->validate([
                'id_weight_bracket_cost' => 'required|integer|exists:weight_bracket_costs,id_weight_bracket_cost',
                'min_weight' => 'required|numeric|min:0',
            ]);

            $id = $request->input('id_weight_bracket_cost'); // Assuming the ID is passed in the route

            $checkWeightBracket = DB::table('weight_bracket_costs')
                ->where('min_weight', $request->min_weight)
                ->where('id_weight_bracket_cost', '!=', $id)
                ->first();

            if ($checkWeightBracket) {
                throw new Exception('Weight bracket already exists with the given minimum weight.');
            }

            $updateWeightBracket = DB::table('weight_bracket_costs')
                ->where('id_weight_bracket_cost', $id)
                ->update([
                    'min_weight' => $request->min_weight,
                    'updated_by' => $request->user()->id, // Assuming the user is authenticated
                    'updated_at' => now(),
                ]);
                if (!$updateWeightBracket) {
                    throw new Exception('Failed to update weight bracket.');
                }

            DB::commit();
            return ResponseHelper::success('Weight bracket updated successfully.',null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function deleteWeightBracketCost(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_weight_bracket_cost'); // Assuming the ID is passed in the route
            $request->validate([
                'id_weight_bracket_cost' => 'required|integer|exists:weight_bracket_costs,id_weight_bracket_cost',
            ]);

            $deleted = DB::table('weight_bracket_costs')
                ->where('id_weight_bracket_cost', $id)
                ->update(['deleted_at' => now(), 'deleted_by' => Auth::id(), 'status' => 'inactive']);

            if ($deleted) {
                DB::commit();
                return ResponseHelper::success('Weight bracket deleted successfully.', null, 200);
            } else {
                throw new Exception('Failed to delete weight bracket.');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

     public function restoreWeightBracketCost(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_weight_bracket_cost'); // Assuming the ID is passed in the route
            $request->validate([
                'id_weight_bracket_cost' => 'required|integer|exists:weight_bracket_costs,id_weight_bracket_cost',
            ]);

            $restoreWeightBracket = DB::table('weight_bracket_costs')
                ->where('id_weight_bracket_cost', $id)
                ->update(['deleted_at' => null, 'deleted_by' => null, 'status' => 'active']);

            DB::commit();
            return ResponseHelper::success('Weight bracket restored successfully.',null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function createWeightBracketSelling(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'min_weight' => 'required|numeric|min:0',
            ]);

            $weightBracket = DB::table('weight_bracket_selling')->insert([
                'min_weight' => $request->min_weight,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]);

            DB::commit();
            return ResponseHelper::success('Weight bracket created successfully.', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    
    public function getWeightBracketsSelling(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'weight_bracket_selling.id_weight_bracket_selling',
            'weight_bracket_selling.min_weight',
            'weight_bracket_selling.created_at',
            'weight_bracket_selling.updated_at',
            'weight_bracket_selling.deleted_at',
            'weight_bracket_selling.created_by',
            'weight_bracket_selling.updated_by',
            'users.name as created_by_name'
        ];

        $brackets = DB::table('weight_bracket_selling')
            ->select($select)
            ->join('users', 'weight_bracket_selling.created_by', '=', 'users.id')
            ->when($search, function ($query) use ($search) {
                return $query->where('weight_bracket_selling.min_weight', 'like', '%' . $search . '%');
            })
            ->orderBy('weight_bracket_selling.id_weight_bracket_selling', 'asc')
            ->paginate($limit);

        return ResponseHelper::success('Weight brackets retrieved successfully.', $brackets, 200);
    }

    
    public function updateWeightBracketSelling(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_weight_bracket_selling'); // Assuming the ID is passed in the route
            $request->validate([
                'id_weight_bracket_selling' => 'required|integer|exists:weight_bracket_selling,id_weight_bracket_selling',
                'min_weight' => 'required|numeric|min:0',
            ]);

            $checkWeightBracket = DB::table('weight_bracket_selling')
                ->where('min_weight', $request->min_weight)
                ->where('id_weight_bracket_selling', '!=', $id)
                ->first();

            if ($checkWeightBracket) {
                throw new Exception('Weight bracket already exists with the given minimum weight.');
            }

            $updateWeightBracket = DB::table('weight_bracket_selling')
                ->where('id_weight_bracket_selling', $id)
                ->update([
                    'min_weight' => $request->min_weight,
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ]);

            DB::commit();
            return ResponseHelper::success('Weight bracket updated successfully.',null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    

    public function deleteWeightBracketSelling(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_weight_bracket_selling'); // Assuming the ID is passed in the route
            $request->validate([
                'id_weight_bracket_selling' => 'required|integer|exists:weight_bracket_selling,id_weight_bracket_selling',
            ]);

            $deleteWeightBracket = DB::table('weight_bracket_selling')
                ->where('id_weight_bracket_selling', $id)
                ->update(['deleted_at' => now(), 'deleted_by' => Auth::id(), 'status' => 'inactive']);

            DB::commit();
            return ResponseHelper::success('Weight bracket deleted successfully.',null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

   
    public function restoreWeightBracketSelling(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_weight_bracket_selling'); // Assuming the ID is passed in the route
            $request->validate([
                'id_weight_bracket_selling' => 'required|integer|exists:weight_bracket_selling,id_weight_bracket_selling',
            ]);

            $restoreWeightBracket = DB::table('weight_bracket_selling')
                ->where('id_weight_bracket_selling', $id)
                ->update(['deleted_at' => null, 'deleted_by' => null, 'status' => 'active']);

            DB::commit();
            return ResponseHelper::success('Weight bracket restored successfully.',null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
