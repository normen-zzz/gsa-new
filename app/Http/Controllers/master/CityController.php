<?php

namespace App\Http\Controllers\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\Auth;
use Exception;

class CityController extends Controller
{
    public function createcity(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'id_country' => 'required|exists:country,id_country',
                'name_city' => 'required|string|max:100|unique:city,name_city',
                'status' => 'required|boolean',
            ]);
            $data['created_by'] = $request->user()->id_user;
            $data['created_at'] = now();
            $data['updated_at'] = now();
            $city = DB::table('city')->insert($data);

            if ($city) {
                DB::commit();
                return ResponseHelper::success('city created successfully.', NULL, 201);
            } else {
                throw new Exception('Failed to create city.');
            }
        } catch (Exception $th) {
            DB::rollback();
            return ResponseHelper::error($th);
        }
    }

    public function getcity(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [

            'city.id_city',
            'city.id_country',
            'countries.name_country',
            'city.name_city',
            'city.status',
            'city.created_at',
            'users.name as created_by',
            'city.deleted_at'
        ];
        $query = DB::table('city')
            ->select($select)
            ->leftJoin('users', 'city.created_by', '=', 'users.id_user')
            ->leftJoin('countries', 'city.id_country', '=', 'countries.id_country')
            ->where('city.name_city', 'like', '%' . $search . '%')
            ->orderBy('city.created_at', 'desc');


        $city = $query->paginate($limit);


        return response()->json([
            'status' => 'success',
            'data' => $city,
            'meta_data' => [
                'code' => 200,
                'message' => 'city retrieved successfully.',
                'total' => $city->total(),
            ],
        ]);
    }

    public function deactivatecity(Request $request)
    {


        DB::beginTransaction();
        try {
            $id = $request->input('id_city');
            $city = DB::table('city')->where('id_city', $id)->first();

            if (!$city) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'city not found.',
                ], 404);
            }
            $update = DB::table('city')
                ->where('id_city', $id)
                ->update(['status' => false, 'deleted_by' => $request->user()->id_user, 'deleted_at' => now()]);

            $changes = [
                'type' => 'deactivate',
                'old' => [
                    'status' => $city->status,

                ],
                'new' => [
                    'status' => false,
                    'deleted_by' => $request->user()->id_user,
                    'deleted_at' => now(),
                ]
            ];
            if ($update) {
                $insertLog = DB::table('log_city')->insert([
                    'id_city' => $id,
                    'action' => json_encode($changes),
                    'id_user' => Auth::user()->id_user,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                if (!$insertLog) {
                    throw new Exception('Failed to log deactivation action.');
                } else {
                    DB::commit();
                    return response()->json([
                        'code' => 200,
                        'status' => 'success',
                        'data' => $city,
                        'meta_data' => [
                            'code' => 200,
                            'message' => 'city deactivated successfully.',
                        ]
                    ], 200);
                }
            } else {
                throw new Exception('Failed to deactivate city.');
            }
        } catch (Exception $th) {
            DB::rollback();
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'meta_data' => [
                    'code' => 500,
                    'message' => 'Failed to deactivate city: ',
                    'errors' => $th->getMessage(),
                ]
            ], 500);
        }
    }

    public function activatecity(Request $request)
    {

        DB::beginTransaction();
        try {
            $id = $request->input('id_city');
            $city = DB::table('city')->where('id_city', $id)->first();

            if (!$city) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'city not found.',
                ], 404);
            }

            $update = DB::table('city')
                ->where('id_city', $id)
                ->update(['status' => true]);
            $changes = [
                'type' => 'activate',
                'old' => [
                    'status' => !$city->status,
                    'deleted_by' => $city->deleted_by,
                    'deleted_at' => $city->deleted_at,
                ],
                'new' => [
                    'status' => true,
                    'deleted_by' => null,
                    'deleted_at' => null,
                ]
            ];

            if ($update) {
                $insertLog = DB::table('log_city')->insert([
                    'id_city' => $id,
                    'action' => json_encode($changes),
                    'id_user' => request()->user()->id_user,
                ]);
                if (!$insertLog) {
                    throw new Exception('Failed to log activation action.');
                } else {
                    DB::commit();
                    return response()->json([
                        'code' => 200,
                        'status' => 'success',
                        'data' => $city,
                        'meta_data' => [
                            'code' => 200,
                            'message' => 'city activated successfully.',

                        ]
                    ], 200);
                }
            }
        } catch (Exception $th) {
            DB::rollback();
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'meta_data' => [
                    'code' => 500,
                    'message' => 'Failed to activate city: ',
                    'errors' => $th->getMessage(),
                ]
            ], 500);
        }
    }

    public function updatecity(Request $request)
    {
        DB::beginTransaction();
        try {
            $city = DB::table('city')->where('id_city', $request->input('id_city'))->first();
            $validated = $request->validate([
                'id_city' => 'required|exists:city,id_city',
                'name_city' => 'required|string|max:100|unique:city,name_city,' . $request->input('id_city') . ',id_city',
                'status' => 'required|boolean',
            ]);

            $validated['updated_at'] = now();
            $validated['updated_by'] = $request->user()->id_user;

            $updated = DB::table('city')
                ->where('id_city', $validated['id_city'])
                ->update($validated);
            $changes = [];
            foreach ($validated as $key => $value) {
                if ($city->$key !== $value) {
                    $changes[$key] = [
                        'old' => $city->$key,
                        'new' => $value,
                    ];
                }
            }
            if (!empty($changes)) {
                DB::table('log_city')->insert([
                    'id_city' => $validated['id_city'],
                    'action' => json_encode($changes),
                    'id_user' => $request->user()->id_user,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (!$updated) {
                throw new Exception('Failed to update city.');
            }
            DB::commit();
            return ResponseHelper::success('city updated successfully.', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
