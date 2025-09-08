<?php

namespace App\Http\Controllers\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Helpers\ResponseHelper;

date_default_timezone_set('Asia/Jakarta');

class UsersController extends Controller
{
    public function getUsers(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');

        $select = [
            'users.id_user',
            'users.name',
            'users.email',
            'users.status',
            'users.created_at',
            'positions.id_position',
            'positions.name as position_name',
            'divisions.id_division',
            'divisions.name as division_name',
        ];

        $query = DB::table('users')
            ->select($select)
            ->join('positions', 'users.id_position', '=', 'positions.id_position')
            ->join('divisions', 'users.id_division', '=', 'divisions.id_division')
            ->when($search, function ($query, $search) {
                return $query
                    ->where('users.name', 'like', '%' . $search . '%')
                    ->orWhere('users.email', 'like', '%' . $search . '%');
            })
            ->orderBy('users.created_at', 'desc');

        $users = $query->paginate($limit);

        return ResponseHelper::success('Users retrieved successfully.', $users, 200);
    }
    public function getUserById($id)
    {
        $select = [
            'users.id_user',
            'users.name',
            'users.email',
            'users.status',
            'users.created_at',
            'positions.id_position',
            'positions.name as position_name',
            'divisions.id_division',
            'divisions.name as division_name',
        ];
        $user = DB::table('users')
            ->select($select)
            ->join('positions', 'users.id_position', '=', 'positions.id_position')
            ->join('divisions', 'users.id_division', '=', 'divisions.id_division')
            ->where('users.id_user', $id)
            ->first();

        if (!$user) {
            return ResponseHelper::success('User not found.', NULL, 404);
        }

        return ResponseHelper::success('User retrieved successfully.', $user, 200);
    }
}
