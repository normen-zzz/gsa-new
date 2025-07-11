<?php

namespace App\Http\Controllers;

use App\Models\User;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
        
   

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:5',
            'id_position' => 'required|integer',
            'id_division' => 'required|integer',
            'id_role' => 'required|integer',
            
        ]);

        $data['password'] = bcrypt($data['password']);

        $user = User::create($data);
        $token = Auth::login($user);

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully.',
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'Bearer',
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:5',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
            'status' => 'error',
            'code' => 401,
            'meta_data' => [
                'code' => 401,
                'message' => 'Invalid credentials.',
            ],
        ], 401);
        }

        $user = Auth::user();
        $token = Auth::claims([
            'id_user' => $user->id_user,
            'name' => $user->name,
            'email' => $user->email,
            'id_position' => $user->id_position,
            'id_division' => $user->id_division,
            'id_role' => $user->id_role
        ])->attempt($credentials);
       

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'meta_data' => [
                    'code' => 500,
                    'message' => 'Failed to create token.',
                ],
            ], 500);
        } else{
            return response()->json([
               'status' => 'success',
                'code' => 200,
                'data' => [
                    'token' => $token,
                ],
                'meta_data' => [
                    'code' => 200,
                    'message' => 'User logged in successfully.',
                ],
            ], 200);
        }
    }   

    public function logout()
    {
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'meta_data' => [
                'code' => 200,
                'message' => 'User logged out successfully.',
            ],
        ], 200);
    }

    public function refresh()
    {
        $token = Auth::refresh();
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => [
                'token' => $token,
                'type' => 'Bearer',
            ],
            'meta_data' => [
                'code' => 200,
                'message' => 'Token refreshed successfully.',
            ],
        ], 200);
    }
}
