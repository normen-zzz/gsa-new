<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\DB;
use Exception;

class RouteController extends Controller
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
            'pol.name as pol_name',
            'routes.pod',
            'pod.name as pod_name',
            'routes.name',
            'routes.description',
            'routes.created_at',
            'created_by.name as created_by_name'
        ];

        $routes = DB::table('routes')
            ->select($select)
            ->leftJoin('airlines', 'routes.airline', '=', 'airlines.id_airline')
            ->leftJoin('airports as pol', 'routes.pol', '=', 'pol.id_airport')
            ->leftJoin('airports as pod', 'routes.pod', '=', 'pod.id_airport')
            ->leftJoin('users as created_by', 'routes.created_by', '=', 'created_by.id')
            ->when($search, function ($query) use ($search) {
                $query->where('routes.name', 'like', '%' . $search . '%')
                    ->orWhere('airlines.name', 'like', '%' . $search . '%')
                    ->orWhere('pol.name', 'like', '%' . $search . '%')
                    ->orWhere('pod.name', 'like', '%' . $search . '%');
                return $query;
            })
            ->orderBy('routes.id_route', 'asc')
            ->paginate($limit);

        return ResponseHelper::success('Routes retrieved successfully.', $routes, 200);
    }

    public function getRouteById($id)
    {
        $route = DB::table('routes')
            ->select('routes.*', 'airlines.name as airline_name', 'pol.name as pol_name', 'pod.name as pod_name')
            ->leftJoin('airlines', 'routes.airline', '=', 'airlines.id_airline')
            ->leftJoin('airports as pol', 'routes.pol', '=', 'pol.id_airport')
            ->leftJoin('airports as pod', 'routes.pod', '=', 'pod.id_airport')
            ->where('id_route', $id)
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
                'pod' => 'required|exists:airports,id_airport',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
            ]);

            $data['created_by'] = $request->user()->id; // Assuming the user is authenticated
            $data['created_at'] = now();

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

    public function updateRoute(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'airline' => 'required|exists:airlines,id_airline',
                'pol' => 'required|exists:airports,id_airport',
                'pod' => 'required|exists:airports,id_airport',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
            ]);

            $data['updated_by'] = $request->user()->id; // Assuming the user is authenticated
            $data['updated_at'] = now();

            $updateRoute = DB::table('routes')->where('id_route', $id)->update($data);

            if ($updateRoute) {
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
    public function deleteRoute($id)
    {
        DB::beginTransaction();
        try {
            $deleted = DB::table('routes')
                ->where('id_route', $id)
                ->update(['deleted_at' => now(), 'deleted_by' => 1]); // Assuming deleted_by is always 1 for this example

            if ($deleted) {
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
}
