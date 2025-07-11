<?php

namespace App\Http\Controllers\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    public function getUsers(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');

        $query = DB::table('users')
            ->select('users.id_user', 'users.name', 'users.email', 'users.status', 'users.created_at', 'roles.name as role_name')
            ->join('roles', 'users.id_role', '=', 'roles.id_role')
            ->where('users.name', 'like', '%' . $search . '%')
            ->orWhere('users.email', 'like', '%' . $search . '%')
            ->orderBy('users.created_at', 'desc');

        $users = $query->paginate($limit);

        return response()->json([
            'status' => 'success',
            'message' => 'Users retrieved successfully.',
            'data' => $users,
        ]);
    }
}
