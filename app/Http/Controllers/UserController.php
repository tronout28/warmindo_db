<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function register(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'phone_number' => 'required|string|max:255|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'picture_profile' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('picture_profile')) {
            $imageName = time() . '.' . $request->picture_profile->extension();
            $request->picture_profile->move(public_path('images'), $imageName);
        } else {
            $imageName = null;
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'picture_profile' => $imageName,
            'user_verified' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    public function login(Request $request) {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->login)
                    ->orWhere('username', $request->login)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
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

    public function details() {
        $user = auth()->user();

        return response()->json([
            "success" => true,
            "message" => "User details",
            "user" => $user
        ], 200);
    }

    public function update(Request $request) {
        $user = auth()->user();

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'username' => 'sometimes|required|string|max:255|unique:users,username,' . $user->id,
            'phone_number' => 'sometimes|required|string|max:255|unique:users,phone_number,' . $user->id,
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|nullable|string|min:8',
            'picture_profile' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $user->name = $request->get('name', $user->name);
        $user->username = $request->get('username', $user->username);
        $user->phone_number = $request->get('phone_number', $user->phone_number);
        $user->email = $request->get('email', $user->email);

        if ($request->hasFile('picture_profile')) {
            // Delete old picture if exists
            if ($user->picture_profile) {
                Storage::delete('public/images/' . $user->picture_profile);
            }
            $imageName = time() . '.' . $request->picture_profile->extension();
            $request->picture_profile->move(public_path('images'), $imageName);
            $user->picture_profile = $imageName;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'user' => $user
        ], 200);
    }

    public function index() {
        $users = User::all();

        return response()->json([
            'success' => true,
            'message' => 'User list',
            'data' => $users
        ], 200);
    }
}
