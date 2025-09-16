<?php

namespace App\Http\Controllers\master;

use Exception;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class RuteController extends Controller
{
    public function getRoutes(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'routes.id_route',
            'routes.airline',
            'airlines.name as airline_name',
            'routes.pol',
            'pol.name_airport as pol_name',
            'pol.code_airport as pol_code',
            'routes.pod',
            'pod.name_airport as pod_name',
            'pod.code_airport as pod_code',
            'routes.created_at',
            'routes.updated_at',
            'routes.deleted_at',
            'routes.created_by',
            'routes.updated_by',
            'created_by.name as created_by_name'
        ];

        $routes = DB::table('routes')
            ->select($select)
            ->leftJoin('airlines', 'routes.airline', '=', 'airlines.id_airline')
            ->leftJoin('airports as pol', 'routes.pol', '=', 'pol.id_airport')
            ->leftJoin('airports as pod', 'routes.pod', '=', 'pod.id_airport')
            ->leftJoin('users as created_by', 'routes.created_by', '=', 'created_by.id_user')
            ->when($search, function ($query) use ($search) {
                $query->
                    where('airlines.name', 'like', '%' . $search . '%')
                    ->orWhere('pol.name_airport', 'like', '%' . $search . '%')
                    ->orWhere('pod.name_airport', 'like', '%' . $search . '%');
                return $query;
            })
            ->orderBy('routes.id_route', 'asc')
            ->paginate($limit);

        return ResponseHelper::success('Routes retrieved successfully.', $routes, 200);
    }

    public function getRouteById($id)
    {
        $select = [
            'routes.id_route',
            'routes.airline',
            'airlines.name as airline_name',
            'routes.pol',
            'pol.name_airport as pol_name',
            'pol.code_airport as pol_code',
            'routes.pod',
            'pod.name_airport as pod_name',
            'pod.code_airport as pod_code',
            'routes.created_at',
            'routes.updated_at',
            'routes.deleted_at',
            'routes.created_by',
            'routes.updated_by',
            'created_by.name as created_by_name'
        ];
        $route = DB::table('routes')
            ->select($select)
            ->leftJoin('airlines', 'routes.airline', '=', 'airlines.id_airline')
            ->leftJoin('airports as pol', 'routes.pol', '=', 'pol.id_airport')
            ->leftJoin('airports as pod', 'routes.pod', '=', 'pod.id_airport')
            ->leftJoin('users as created_by', 'routes.created_by', '=', 'created_by.id')
            ->where('routes.id_route', $id)
            ->first();

        if (!$route) {
            return ResponseHelper::success('Route not found.', NULL, 404);
        }

        return ResponseHelper::success('Route retrieved successfully.', $route, 200);
    }

    public function createRoute(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'airline' => 'required|exists:airlines,id_airline',
                'pol' => 'required|exists:airports,id_airport',
                'pod' => 'required|exists:airports,id_airport|different:pol',
            ]);

            $data['created_by'] = Auth::id(); // Assuming the user is authenticated
            $data['created_at'] = now();

            $checkRoute = DB::table('routes')
                ->where('airline', $data['airline'])
                ->where('pol', $data['pol'])
                ->where('pod', $data['pod'])
                ->first();

            if ($checkRoute) {
                throw new Exception('Route already exists.');
            }




            $insertRoute = DB::table('routes')->insertGetId($data);

            if ($insertRoute) {
                DB::commit();
                return ResponseHelper::success('Route created successfully.', NULL, 201);
            } else {
                throw new Exception('Failed to create route.');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }

    public function updateRoute(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_route'); // Assuming the ID is passed in the request
            $route = DB::table('routes')->where('id_route', $id)->first();
            $data = $request->validate([
                'id_route' => 'required|integer|exists:routes,id_route',
                'airline' => 'required|exists:airlines,id_airline',
                'pol' => 'required|exists:airports,id_airport',
                'pod' => 'required|exists:airports,id_airport',

            ]);
            $data['updated_by'] = $request->user()->id; // Assuming the user is authenticated
            $data['updated_at'] = now();


            $checkRoute = DB::table('routes')
                ->where('airline', $data['airline'])
                ->where('pol', $data['pol'])
                ->where('pod', $data['pod'])
                ->where('id_route', '!=', $id)
                ->first();
            if ($checkRoute) {
                throw new Exception('Route already exists.');
            }

            $updateRoute = DB::table('routes')->where('id_route', $id)->update($data);

            $changes = [];
            foreach ($data as $key => $value) {
                if ($route->$key !== $value) {
                    $changes[$key] = [
                        'type' => 'update',
                        'old' => $route->$key,
                        'new' => $value,
                    ];
                }
            }
            if ($updateRoute) {
                if (!empty($changes)) {
                    DB::table('log_routes')->insert([
                        'id_route' => $id,
                        'action' => json_encode($changes),
                        'id_user' => $request->user()->id_user,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                DB::commit();
                return ResponseHelper::success('Route updated successfully.', NULL, 200);
            } else {
                throw new Exception('Failed to update route.');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
    public function deleteRoute(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_route'); // Assuming the ID is passed in the request
            $route = DB::table('routes')->where('id_route', $id)->first();
            if (!$route) {
                throw new Exception('Route not found.');
            }
            $deleted = DB::table('routes')
                ->where('id_route', $id)
                ->update(['deleted_at' => now(), 'deleted_by' => Auth::id(), 'status' => 'inactive']); // Assuming deleted_by is always 1 for this example

            $changes = [
                'type' => 'delete',
                'old' => [
                    'status' => 'active',
                ],
                'new' => [
                    'status' => 'inactive',
                ],
            ];
            if ($deleted) {
                DB::table('log_routes')->insert([
                    'id_route' => $id,
                    'action' => json_encode($changes),
                    'id_user' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::commit();
                return ResponseHelper::success('Route deleted successfully.', NULL, 200);
            } else {
                throw new Exception('Failed to delete route.');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
    public function restoreRoute(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_route'); // Assuming the ID is passed in the request
            $restored = DB::table('routes')
                ->where('id_route', $id)
                ->update(['deleted_at' => null, 'deleted_by' => null, 'status' => 'active']);

            $changes = [
                'type' => 'restore',
                'old' => [
                    'status' => 'inactive',
                ],
                'new' => [
                    'status' => 'active',
                ],
            ];
            if ($restored) {
                DB::table('log_routes')->insert([
                    'id_route' => $id,
                    'action' => json_encode($changes),
                    'id_user' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::commit();
                return ResponseHelper::success('Route restored successfully.', NULL, 200);
            } else {
                throw new Exception('Failed to restore route.');
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e);
        }
    }
}
