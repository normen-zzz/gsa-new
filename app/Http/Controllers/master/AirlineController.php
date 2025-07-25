<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class AirlineController extends Controller
{
    public function getAirlines(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');

        $query = DB::table('airlines')
            ->select('id_airline', 'name', 'code', 'status', 'created_at')
            ->where('name', 'like', '%' . $search . '%')
            ->orWhere('code', 'like', '%' . $search . '%')
            ->orderBy('created_at', 'desc');

        $airlines = $query->paginate($limit);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => $airlines,
            'meta_data' => [
                'code' => 200,
                'message' => 'Airlines retrieved successfully.',
            ]
        ], 200);
    }

    public function getAirlineById($id)
    {
        $airline = DB::table('airlines')
            ->where('id_airline', $id)
            ->first();

        if (!$airline) {
            return response()->json([
                'status' => 'error',
                'message' => 'Airline not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => $airline,
            'meta_data' => [
                'code' => 200,
                'message' => 'Airline retrieved successfully.',
            ]
        ], 200);
    }

    public function createAirline(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:airlines,code',
                'status' => 'required|boolean',
            ]);

            $insertAirline = DB::table('airlines')->insertGetId([
                'name' => $data['name'],
                'code' => $data['code'],
                'status' => $data['status'],
                'created_at' => now(),
            ]);

            if ($insertAirline) {
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Airline created successfully.',
                    'data' => [
                        'id_airline' => $insertAirline,
                        'name' => $data['name'],
                        'code' => $data['code'],
                        'status' => $data['status'],
                        'created_at' => now(),
                    ],
                ], 201);
            } else {
                throw new Exception('Failed to create airline.');
            }
        } catch (Exception $e) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'status' => 'error',
                    'code' => 422,
                    'meta_data' => [
                        'code' => 422,
                        'message' => 'Validation errors occurred.',
                        'errors' => $e->validator->errors()->toArray(),
                    ],
                ], 422);
            }

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'meta_data' => [
                    'code' => 500,
                    'message' => 'An error occurred while creating the airline.' . $e->getMessage(),
                ],

            ], 500);
        }
    }

    public function updateAirline(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:10|unique:airlines,code,' . $id . ',id_airline',
                'status' => 'required|boolean',
            ]);

            $updateAirline = DB::table('airlines')
                ->where('id_airline', $id)
                ->update([
                    'name' => $data['name'],
                    'code' => $data['code'],
                    'status' => $data['status'],
                    'updated_at' => now(),
                ]);

            if ($updateAirline) {
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Airline updated successfully.',
                ], 200);
            } else {
                throw new Exception('Failed to update airline.');
            }
        } catch (Exception $e) {
            DB::rollback();
            if ($e instanceof ValidationException) {
                return response()->json([
                    'status' => 'error',
                    'code' => 422,
                    'meta_data' => [
                        'code' => 422,
                        'message' => 'Validation errors occurred.',
                        'errors' => $e->validator->errors()->toArray(),
                    ],
                ], 422);
            }

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'meta_data' => [
                    'code' => 500,
                    'message' => 'An error occurred while updating the airline: ' . $e->getMessage(),
                ],
            ], 500);
        }
    }
}
