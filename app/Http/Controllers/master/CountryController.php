<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;


class CountryController extends Controller
{
    public function createCountry(Request $request)
    {
        $data = $request->validate([
            'name_country' => 'required|string|max:100|unique:countries,name_country',

        ]);

        $data['created_by'] = $request->user()->id_user;

        DB::beginTransaction();
        try {
            $country = DB::table('countries')->insert($data);

            if ($country) {
                return response()->json([
                    'status' => 'success',
                    'code' => 201,
                    'data' => $data,
                    'meta_data' => [
                        'code' => 201,
                        'message' => 'Country created successfully.',
                    ]
                ], 201);
            } else {
                throw new Exception('Failed to create country.');
            }
            DB::commit();
        } catch (Exception $th) {
            DB::rollback();
            if ($th instanceof ValidationException) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'meta_data' => [
                        'code' => 422,
                        'message' => 'Validation errors occurred.',
                        'errors' => $th->validator->errors()->toArray(),
                    ],
                ], 422);
            } else{
                // Handle other exceptions
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create country: ' . $th->getMessage(),
                    'meta_data' => [
                        'code' => 500,
                        'message' => 'An error occurred while creating the country.',
                        'errors' => $th->getMessage(),
                    ],
                ], 500);
            }
           
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
            ->join('users', 'countries.created_by', '=', 'users.id_user')
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
}
