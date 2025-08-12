<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Helpers\ResponseHelper;

date_default_timezone_set('Asia/Jakarta');


class PositionController extends Controller
{
    public function getPositions(Request $request)

    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'positions.id_position',
            'positions.name',
            'positions.description',
            'positions.status',
            'positions.created_at'
        ];

        $positions = DB::table('positions')
            ->select($select)
            ->when($search, function ($query) use ($search) {
                return $query->where('positions.name', 'like', '%' . $search . '%');
            })
            ->orderBy('positions.id_position', 'asc')
            ->paginate($limit);

        return ResponseHelper::success('Positions retrieved successfully.', $positions, 200);
    }

    public function getPositionById($id)
    {
        $position = DB::table('positions')
            ->where('id_position', $id)
            ->first();

        if (!$position) {
            return ResponseHelper::success('Position not found.', NULL, 404);
        }

        return ResponseHelper::success('Position retrieved successfully.', $position, 200);
    }

    public function createPosition(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:positions,name',
                'description' => 'nullable|string|max:500',
                'status' => 'required|boolean'
            ]);
            $position = DB::table('positions')->insertGetId([
                'name' => $request->input('name'),
                'description' => $request->input('description', null),
                'status' => $request->input('status', true),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            DB::commit();
            return ResponseHelper::success('Position created successfully.', NULL, 201);
        } catch (Exception $th) {
            DB::rollBack();
            return ResponseHelper::error($th);
            //throw $th;
        }
    }

    public function updatePosition(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_position' => 'required|exists:positions,id_position',
                'name' => 'required|string|max:255|unique:positions,name,' . $request->input('id_position') . ',id_position',
                'description' => 'nullable|string|max:500',
                'status' => 'required|boolean'
            ]);
            // Check if the position exists
            $check = DB::table('positions')->where('id_position', $request->input('id_position'))->first();
            if (!$check) {
                return ResponseHelper::success('Position not found.', NULL, 404);
            }

            $position = DB::table('positions')
                ->where('id_position', $request->input('id_position'))
                ->update([
                    'name' => $request->input('name'),
                    'description' => $request->input('description', null),
                    'status' => $request->input('status', true),
                    'updated_at' => now()
                ]);
            $changes = [];
            foreach ($request->all() as $key => $value) {
                if ($check->$key != $value) {
                    $changes[$key] = [
                        'type' => 'update',
                        'old' => $check->$key,
                        'new' => $value
                    ];
                }
            }
            

            if (!$position) {
                DB::commit(); // Still commit as no error occurred, just no changes
                return ResponseHelper::success('No changes made to the position.', NULL, 200);
            } else {
                DB::table('log_position')->insert([
                    'id_position' => $request->input('id_position'),
                    'action' => json_encode($changes),
                    'id_user' => $request->user()->id_user,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();
                return ResponseHelper::success('Position updated successfully.', NULL, 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
