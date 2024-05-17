<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function register(Request $request) 
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:users',
            'phone_number' => 'required|string|max:13|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'username' => $request->username,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'user_verified' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'username' => 'required|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !\Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        $token = $user->createToken('warmindo')->plainTextToken; 

        return response()->json([
            'success' => true,
            'message' => 'User login successfully',
            'user' => $user,
            'token' => $token
        ], 200);
    }

    public function details()
    {
        $user = auth()->user();

        return response([
            "success" => true,
            "message" => "User details",
            "user" => $user
        ], 200);
    }
}