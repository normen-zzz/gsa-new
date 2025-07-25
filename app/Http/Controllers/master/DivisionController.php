<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Helpers\ResponseHelper;
use GuzzleHttp\Psr7\Response;

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
        return ResponseHelper::success('Divisions retrieved successfully.', $divisions->items(), 200);
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

        return ResponseHelper::success('Division retrieved successfully.', $division, 200);
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

            return ResponseHelper::success('Division created successfully.', NULL, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
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

            return ResponseHelper::success('Division updated successfully.', NULL, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
