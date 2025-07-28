<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use GuzzleHttp\Psr7\Response;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;


class CountryController extends Controller
{
    public function createCountry(Request $request)
    {


        DB::beginTransaction();
        try {
            $data = $request->validate([
                'name_country' => 'required|string|max:100|unique:countries,name_country',
                'status' => 'required|boolean',

            ]);

            $data['created_by'] = $request->user()->id_user;
            $data['created_at'] = now();
            $data['updated_at'] = now();
            $country = DB::table('countries')->insert($data);

            if ($country) {
                DB::commit();
                return ResponseHelper::success('Country created successfully.', NULL, 201);
            } else {
                throw new Exception('Failed to create country.');
            }
        } catch (Exception $th) {
            DB::rollback();
            return ResponseHelper::error($th);
        }
    }

    public function getCountry(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'countries.id_country',
            'countries.name_country',
            'countries.status',
            'countries.created_at',
            'users.name as created_by'
        ];
        $query = DB::table('countries')
            ->select($select)
            ->leftJoin('users', 'countries.created_by', '=', 'users.id_user')
            ->where('countries.name_country', 'like', '%' . $search . '%')
            ->orderBy('countries.created_at', 'desc');


        $countries = $query->paginate($limit);


        return response()->json([
            'status' => 'success',
            'data' => $countries,
            'meta_data' => [
                'code' => 200,
                'message' => 'Countries retrieved successfully.',
                'total' => $countries->total(),
            ],
        ]);
    }

    public function deactivateCountry(Request $request)
    {
        $id = $request->input('id_country');
        $country = DB::table('countries')->where('id_country', $id)->first();

        if (!$country) {
            return response()->json([
                'status' => 'error',
                'message' => 'Country not found.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $update = DB::table('countries')
                ->where('id_country', $id)
                ->update(['status' => false]);
            if ($update) {
                $insertLog = DB::table('log_country')->insert([
                    'id_country' => $id,
                    'action' => 'deactivate Id Country: ' . $id . ' Name Country: ' . $country->name_country,
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
                        'data' => $country,
                        'meta_data' => [
                            'code' => 200,
                            'message' => 'Country deactivated successfully.',
                        ]
                    ], 200);
                }
            } else {
                throw new Exception('Failed to deactivate country.');
            }
        } catch (Exception $th) {
            DB::rollback();
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'meta_data' => [
                    'code' => 500,
                    'message' => 'Failed to deactivate country: ',
                    'errors' => $th->getMessage(),
                ]
            ], 500);
        }
    }

    public function activateCountry(Request $request)
    {
        $id = $request->input('id_country');
        $country = DB::table('countries')->where('id_country', $id)->first();

        if (!$country) {
            return response()->json([
                'status' => 'error',
                'message' => 'Country not found.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $update = DB::table('countries')
                ->where('id_country', $id)
                ->update(['status' => true]);

            if ($update) {
                $insertLog = DB::table('log_country')->insert([
                    'id_country' => $id,
                    'action' => 'Activate Id Country: ' . $id . ' Name Country: ' . $country->name_country,
                    'id_user' => request()->user()->id_user,
                ]);
                if (!$insertLog) {
                    throw new Exception('Failed to log activation action.');
                } else {
                    DB::commit();
                    return response()->json([
                        'code' => 200,
                        'status' => 'success',
                        'data' => $country,
                        'meta_data' => [
                            'code' => 200,
                            'message' => 'Country activated successfully.',

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
                    'message' => 'Failed to activate country: ',
                    'errors' => $th->getMessage(),
                ]
            ], 500);
        }
    }

    public function updateCountry(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'id_country' => 'required|exists:countries,id_country',
                'name_country' => 'required|string|max:100|unique:countries,name_country,' . $request->input('id_country') . ',id_country',
                'status' => 'required|boolean',
            ]);

            $validated['updated_at'] = now();
            $validated['updated_by'] = $request->user()->id_user;

            $updated = DB::table('countries')
                ->where('id_country', $validated['id_country'])
                ->update($validated);

            if (!$updated) {
                throw new Exception('Failed to update country.');
            }

            DB::commit();

            return ResponseHelper::success('Country updated successfully.', null, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
