<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class DivisionController extends Controller
{
    public function getDivisions(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'divisions.id_division',
            'divisions.name',
            'divisions.description',
            'divisions.have_role',
            'divisions.status',
            'divisions.created_at'
        ];

        $divisions = DB::table('divisions')
            ->select($select)
            ->when($search, function ($query) use ($search) {
                return $query->where('divisions.name', 'like', '%' . $search . '%');
            })
            ->orderBy('divisions.id_division', 'asc')
            ->paginate($limit);

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $divisions,
            'meta_data' => [
                'code' => 200,
                'message' => 'Divisions retrieved successfully.',
            ]
        ], 200);
    }
    public function getDivisionById($id)
    {
        $division = DB::table('divisions')
            ->where('id_division', $id)
            ->first();

        if (!$division) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Division not found.'
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'data' => $division,
            'meta_data' => [
                'code' => 200,
                'message' => 'Division retrieved successfully.',
            ]
        ], 200);
    }
    public function createDivision(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:divisions,name',
                'description' => 'nullable|string|max:500',
                'have_role' => 'boolean',
                'status' => 'boolean'
            ]);

            $division = DB::table('divisions')->insertGetId([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'have_role' => $request->input('have_role', false),
                'status' => $request->input('status', true),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'code' => 201,
                'status' => 'success',
                'data' => ['id_division' => $division],
                'meta_data' => [
                    'code' => 201,
                    'message' => 'Division created successfully.',
                ]
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
           if ($e instanceof ValidationException) {
                return response()->json([
                    'code' => 422,
                    'status' => 'error',
                   
                    'meta_data' => [
                        'code' => 422,
                        'message' => 'Validation errors occurred.',
                        'errors' => $e->validator->errors()->toArray(),
                    ],
                ], 422);
            } else {
                return response()->json([
                    'code' => 500,
                    'status' => 'error',
                    'meta_data' => [
                        'code' => 500,
                        'message' => 'An error occurred while creating the division: ' . $e->getMessage(),
                        
                    ],
                ], 500);
            }
        }
    }

    public function updateDivision(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_division');
            $request->validate([
                'name' => 'required|string|max:255|unique:divisions,name,' . $id . ',id_division',
                'description' => 'nullable|string|max:500',
                'have_role' => 'boolean',
                'status' => 'boolean'
            ]);

            $updated = DB::table('divisions')
                ->where('id_division', $id)
                ->update([
                    'name' => $request->input('name'),
                    'description' => $request->input('description', null),
                    'have_role' => $request->input('have_role', false),
                    'status' => $request->input('status', true),
                    'updated_at' => now()
                ]);

            if (!$updated) {
                throw new Exception('Failed to update division.');
            }

            DB::commit();

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => ['id_division' => $id],
                'meta_data' => [
                    'code' => 200,
                    'message' => 'Division updated successfully.',
                ]
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            if ($e instanceof ValidationException) {
                return response()->json([
                    'code' => 422,
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'meta_data' => [
                        'code' => 422,
                        'message' => 'Validation errors occurred.',
                        'errors' => $e->validator->errors()->toArray(),
                    ],
                ], 422);
            } else {
                return response()->json([
                    'code' => 500,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'meta_data' => [
                        'code' => 500,
                        'message' => 'An error occurred while updating the division.',
                    ],
                ], 500);
            }
        }
    }
}
