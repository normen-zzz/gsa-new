<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;


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

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $positions,
            'meta_data' => [
                'code' => 200,
                'message' => 'Positions retrieved successfully.',
            ]
        ], 200);
    }

    public function getPositionById($id)
    {
        $position = DB::table('positions')
            ->where('id_position', $id)
            ->first();

        if (!$position) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Position not found.'
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $position,
            'meta_data' => [
                'code' => 200,
                'message' => 'Position retrieved successfully.',
            ]
        ], 200);
    }

    public function createPosition(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:positions,name',
                'description' => 'nullable|string|max:500',
                'status' => 'boolean'
            ]);
            $position = DB::table('positions')->insertGetId([
                'name' => $request->input('name'),
                'description' => $request->input('description', null),
                'status' => $request->input('status', true),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            DB::commit();
            return response()->json([
                'code' => 201,
                'status' => 'success',
                'meta_data' => [
                    'code' => 201,
                    'message' => 'Position created successfully.',
                ]
            ], 201);
        } catch (Exception $th) {
            DB::rollBack();
            if ($th instanceof ValidationException) {
                return response()->json([
                    'code' => 422,
                    'status' => 'error',
                    'meta_data' => [
                        'code' => 422,
                        'message' => 'Validation failed.',
                        'errors' => $th->validator->errors()
                    ]
                ], 422);
            } else {
                return response()->json([
                    'code' => 500,
                    'status' => 'error',
                   
                    'meta_data' => [
                        'code' => 500,
                        'message' => $th->getMessage(),
                    ],
                ], 500);
            }
            //throw $th;
        }



        return response()->json([
            'code' => 201,
            'status' => 'success',
            'data' => $position,
            'meta_data' => [
                'code' => 201,
                'message' => 'Position created successfully.',
            ]
        ], 201);
    }

    public function updatePosition(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'id_position' => 'required|exists:positions,id_position',
                'name' => 'required|string|max:255|unique:positions,name,' . $request->input('id_position') . ',id_position',
                'description' => 'nullable|string|max:500',
                'status' => 'boolean'
            ]);

            $position = DB::table('positions')
                ->where('id_position', $request->input('id_position'))
                ->update([
                    'name' => $request->input('name'),
                    'description' => $request->input('description', null),
                    'status' => $request->input('status', true),
                    'updated_at' => now()
                ]);

            if (!$position) {
                DB::commit(); // Still commit as no error occurred, just no changes
                return response()->json([
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'No changes were made to the position.',
                ], 200);
            } else {
                DB::commit();
                return response()->json([
                    'code' => 200,
                    'status' => 'success',
                    'data' => $position,
                    'meta_data' => [
                        'code' => 200,
                        'message' => 'Position updated successfully.',
                    ]
                ], 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Failed to update position: ' . $e->getMessage()
            ], 500);
        }
    }
}
