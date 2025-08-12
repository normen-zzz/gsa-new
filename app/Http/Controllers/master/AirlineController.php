<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Helpers\ResponseHelper;
use GuzzleHttp\Psr7\Response;

date_default_timezone_set('Asia/Jakarta');
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
            ->orderBy('id_airline', 'asc');

        $airlines = $query->paginate($limit);

        return ResponseHelper::success('Airlines retrieved successfully.', $airlines->items(), 200);
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

        return ResponseHelper::success('Airline retrieved successfully.', $airline, 200);
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
                'status' => $data['status'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => $request->user()->id_user,
            ]);

            if ($insertAirline) {
                DB::commit();
                return ResponseHelper::success('Airline created successfully.', NULL, 201);
            } else {
                throw new Exception('Failed to create airline.');
            }
        } catch (Exception $e) {
            return ResponseHelper::error($e);
        }
    }

    public function updateAirline(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_airline');
            $airline = DB::table('airlines')->where('id_airline', $id)->first();
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

                $changes = [];
            foreach ($data as $key => $value) {
                if ($airline->$key !== $value) {
                    $changes[$key] = [
                        'old' => $airline->$key,
                        'new' => $value,
                    ];
                }
            }

            if ($updateAirline) {
                if (!empty($changes)) {
                    DB::table('log_airline')->insert([
                        'id_airline' => $id,
                        'action' => json_encode($changes),
                        'id_user' => $request->user()->id_user,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                DB::commit();
                return ResponseHelper::success('Airline updated successfully.', NULL, 200);
            } else {
                throw new Exception('Failed to update airline.');
            }
        } catch (Exception $e) {
            DB::rollback();
            return ResponseHelper::error($e);
        }
    }
}
