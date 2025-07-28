<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Helpers\ResponseHelper;
use GuzzleHttp\Psr7\Response;

class AirportController extends Controller
{
    public function createAirport(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'name_airport' => 'required|string|max:100|unique:airports,name_airport',
                'code_airport' => 'required|string|max:10|unique:airports,code_airport',
                'id_country' => 'required|integer|exists:countries,id_country',
            ]);

            $data['created_by'] = $request->user()->id_user;

            $airport = DB::table('airports')->insert($data);
            if ($airport) {
                DB::commit();
                return ResponseHelper::success('Airport created successfully.', NULL, 201);
            } else {
                throw new Exception('Failed to create airport.');
            }
        } catch (Exception $th) {
            DB::rollback();
            return ResponseHelper::error($th);
        }
    }

    public function getAirport(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'airports.id_airport',
            'airports.name_airport',
            'airports.code_airport',
            'airports.id_country',
            'countries.name_country',
            'airports.status',
            'users.name as created_by',
            'airports.created_at'
        ];
        $query = DB::table('airports')
            ->select($select)
            ->where('airports.name_airport', 'like', '%' . $search . '%')
            ->join('users', 'airports.created_by', '=', 'users.id_user')
            ->join('countries', 'airports.id_country', '=', 'countries.id_country')
            ->orWhere('airports.code_airport', 'like', '%' . $search . '%')
            ->orderBy('airports.created_at', 'desc');

        $airports = $query->paginate($limit);

        return ResponseHelper::success('Airports retrieved successfully.', $airports, 200);
    }

    public function deactivateAirport(request $request)
    {
        $id = $request->input('id_airport');
        $airport = DB::table('airports')->where('id_airport', $id)->first();

        if (!$airport) {
            return response()->json([
                'status' => 'error',
                'message' => 'Airport not found.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $update = DB::table('airports')->where('id_airport', $id)->update(['status' => false]);
            if ($update) {
                $insertLog = DB::table('log_airport')->insert([
                    'id_airport' => $id,
                    'action' => 'Deactivated id_airport: ' . $id . ' - ' . $airport->name_airport,
                    'id_user' => request()->user()->id_user,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                if ($insertLog) {
                    DB::commit();
                    return response()->json([
                        'status' => 'success',
                        'code' => 200,
                        'data' => $airport,
                        'meta_data' => [
                            'code' => 200,
                            'message' => 'Airport ' . $airport->name_airport . ' deactivated successfully.',
                        ]
                    ], 200);
                }
            } else {
                throw new Exception('Failed to deactivate airport.');
            }
            DB::commit();
            return ResponseHelper::success('Airport ' . $airport->name_airport . ' deactivated successfully.', NULL, 200);
        } catch (Exception $th) {
            DB::rollback();
            return ResponseHelper::error($th);
        }
    }

    public function activateAirport(Request $request)
    {
        $id = $request->input('id_airport');
        $airport = DB::table('airports')->where('id_airport', $id)->first();

        if (!$airport) {
            return response()->json([
                'status' => 'error',
                'message' => 'Airport not found.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $update = DB::table('airports')->where('id_airport', $id)->update(['status' => true]);
            if ($update) {
                $insertLog = DB::table('log_airport')->insert([
                    'id_airport' => $id,
                    'action' => 'Activated id_airport: ' . $id . ' - ' . $airport->name_airport,
                    'id_user' => request()->user()->id_user,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                if ($insertLog) {
                    DB::commit();
                    return ResponseHelper::success('Airport ' . $airport->name_airport . ' activated successfully.', NULL, 200);
                }
            } else {
                throw new Exception('Failed to activate airport.');
            }
        } catch (Exception $th) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'meta_data' => [
                    'code' => 500,
                    'message' => 'An error occurred while activating the airport',
                    'errors' => $th->getMessage(),
                ],

            ], 500);
        }
    }

    public function updateAirport(Request $request)
    {
        $id = $request->input('id_airport');
        $data = $request->validate([
            'name_airport' => 'required|string|max:100|unique:airports,name_airport,'.$id.',id_airport',
            'code_airport' => 'required|string|max:10|unique:airports,code_airport,'.$id.',id_airport',
            'id_country' => 'required|integer|exists:countries,id_country',
        ]);

        DB::beginTransaction();
        try {
            $airport = DB::table('airports')->where('id_airport', $id)->first();
            // cek perubahan antara data lama dan baru
            if ($airport) {
                $changes = [];
                foreach ($data as $key => $value) {
                    if ($airport->$key != $value) {
                        $changes[$key] = [
                            'old' => $airport->$key,
                            'new' => $value
                        ];
                    }
                }
                // Always update regardless if data is the same
                DB::table('airports')->where('id_airport', $id)->update($data);
                
                // Log action whether data changed or not
                $actionMessage = count($changes) > 0 
                    ? 'Updated airport with changes: ' . json_encode($changes)
                    : 'Airport update executed (no data changes)';
                
                $log = DB::table('log_airport')->insert([
                    'id_airport' => $id,
                    'action' => $actionMessage,
                    'id_user' => request()->user()->id_user,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                if ($log) {
                    DB::commit();
                    return ResponseHelper::success('Airport updated successfully.', NULL, 200);
                } else {
                    throw new Exception('Failed to log airport update.');
                }
            } else {
                throw new Exception('Airport not found.');
            }
        } catch (Exception $th) {
            DB::rollback();
            return ResponseHelper::error($th);
        }
    }
}
