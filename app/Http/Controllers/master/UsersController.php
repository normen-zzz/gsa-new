<?php

namespace App\Http\Controllers\master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Helpers\ResponseHelper;
use Exception;
use Illuminate\Support\Facades\Auth;

date_default_timezone_set('Asia/Jakarta');

class UsersController extends Controller
{
    public function getUsers(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');

        $select = [
            'users.id_user',
            'users.username',
            'users.name',
            'users.email',
            'users.status',
            'users.created_at',
            'positions.id_position',
            'positions.name as position_name',
            'divisions.id_division',
            'divisions.name as division_name',
            'users.deleted_at',
            'users.deleted_by',
            'users2.name as deleted_by_name'
        ];

        $query = DB::table('users')
            ->select($select)
            ->join('positions', 'users.id_position', '=', 'positions.id_position')
            ->join('divisions', 'users.id_division', '=', 'divisions.id_division')
            ->leftJoin('users as users2', 'users.deleted_by', '=', 'users2.id_user')
            ->when($search, function ($query, $search) {
                return $query
                    ->where('users.name', 'like', '%' . $search . '%')
                    ->orWhere('users.email', 'like', '%' . $search . '%');
            })
            ->orderBy('users.created_at', 'desc');

        $users = $query->paginate($limit);

        return ResponseHelper::success('Users retrieved successfully.', $users, 200);
    }
    public function getUserById(Request $request)
    {
        $id = $request->input('id_user');
        $select = [
            'users.id_user',
            'users.name',
            'users.username',
            'users.email',
            'users.status',
            'users.created_at',
            'positions.id_position',
            'positions.name as position_name',
            'divisions.id_division',
            'divisions.name as division_name',
            'users.deleted_at',
            'users.deleted_by',
            'users2.name as deleted_by_name'
        ];
        $user = DB::table('users')
            ->select($select)
            ->join('positions', 'users.id_position', '=', 'positions.id_position')
            ->join('divisions', 'users.id_division', '=', 'divisions.id_division')
            ->leftJoin('users as users2', 'users.deleted_by', '=', 'users2.id_user')
            ->where('users.id_user', $id)
            ->first();

        if (!$user) {
            return ResponseHelper::success('User not found.', NULL, 404);
        }

        return ResponseHelper::success('User retrieved successfully.', $user, 200);
    }

    public function createUser(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:50|unique:users,username',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8',
                'id_position' => 'required|integer|exists:positions,id_position',
                'id_division' => 'required|integer|exists:divisions,id_division',
                'phone' => 'nullable|string|max:20',
            ]);

            $data = $request->only([
                'name',
                'username',
                'email',
                'password',
                'id_position',
                'id_division',
                'photo',
                'phone'
            ]);
            $data['password'] = bcrypt($data['password']);
            $data['created_at'] = now();
            $data['updated_at'] = now();
            $data['status'] = true;

            $user = DB::table('users')->insert($data);
            if ($user) {
                DB::commit();
                return ResponseHelper::success('User created successfully.', NULL, 201);
            } else {
                throw new Exception('Failed to create user.');
            }
        } catch (Exception $th) {
            DB::rollback();
            return ResponseHelper::error($th);
        }
    }

    public function updateUser(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_user');
            $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:50|unique:users,username,' . $id . ',id_user',
                'email' => 'required|string|email|max:255|unique:users,email,' . $id . ',id_user',
                'password' => 'nullable|string|min:8',
                'id_position' => 'required|integer|exists:positions,id_position',
                'id_division' => 'required|integer|exists:divisions,id_division',
                'photo' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'status' => 'required|boolean',
            ]);
            $data = $request->only([
                'name',
                'username',
                'email',
                'password',
                'id_position',
                'id_division',
                'photo',
                'phone',
                'status'
            ]);
            if (!empty($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            } else {
                unset($data['password']);
            }

            $user = DB::table('users')->where('id_user', $id)->update($data);
            if ($user) {
                DB::commit();
                return ResponseHelper::success('User updated successfully.', NULL, 200);
            } else {
                throw new Exception('Failed to update user.');
            }
        } catch (Exception $th) {
            DB::rollback();
            return ResponseHelper::error($th);
        }
    }

    public function deleteUser(Request $request)
    {
        DB::beginTransaction();
        try {
            $id = $request->input('id_user');
            $request->validate([
                'id_user' => 'required|integer|exists:users,id_user',
            ]);

            $user = DB::table('users')
            ->where('id_user', $id)
            ->update(['status' => false, 'deleted_at' => now(), 'deleted_by' => Auth::id()]);
            if ($user) {
                DB::commit();
                return ResponseHelper::success('User deleted successfully.', NULL, 200);
            } else {
                throw new Exception('Failed to delete user.');
            }
        } catch (Exception $th) {
            DB::rollback();
            return ResponseHelper::error($th);
        }
    }

    public function activateUser(Request $request) {
        DB::beginTransaction();
        try {
            $id = $request->input('id_user');
            $request->validate([
                'id_user' => 'required|integer|exists:users,id_user',
            ]);

            $user = DB::table('users')
            ->where('id_user', $id)
            ->update(['status' => true, 'deleted_at' => null, 'deleted_by' => null]);
            if ($user) {
                DB::commit();
                return ResponseHelper::success('User activated successfully.', NULL, 200);
            } else {
                throw new Exception('Failed to activate user.');
            }
        } catch (Exception $th) {
            DB::rollback();
            return ResponseHelper::error($th);
        }
    }
}
