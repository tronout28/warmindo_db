<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;


class AdminController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = Admin::where('email', $request->email)->first();
        if ($user == null | ! Hash::check($request->password, $user->password)) {
            return response([
                'message' => 'Invalid credentials',
            ], 401);
        }
        $token = $user->createToken('warmindo')->plainTextToken;

        return response([
            'admin' => $user,
            'token' => $token,
        ], 200);
    }

    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'name' => 'required|string|max:255', 
            'email' => 'required|email',
            'phone' => 'required|string',
            'password' => 'required|string|min:8',
        ]);
        $user = Admin::where('email', $request->email)->first();
        if ($user != null) {
            return response([
                'message' => 'Email already exists',
            ], 409);
        }
        $userdata = [
            'username' => $request->username,
            'name' => $request->name, 
            'email' => $request->email,
            'phone_number' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'admin',
        ];
        $user = Admin::create($userdata);
        $token = $user->createToken('warmindo')->plainTextToken;

        return response(['admin' => $user,
            'token' => $token,
        ], 201);
    }

    public function verifyUser($id)
{
    $user = User::find($id);

    if (!$user) {
        return response()->json([
            'message' => 'User not found',
        ], 404);
    }

    // Example: check a condition or separate method for verification logic
    $user->user_verified = true;
    $user->save();

    return response()->json([
        'message' => 'User verified successfully',
        'user' => $user,
    ], 200);
}

public function unverifyUser($id)
{
    $user = User::find($id);

    if (!$user) {
        return response()->json([
            'message' => 'User not found',
        ], 404);
    }

    // Unverify the user
    $user->user_verified = false;
    $user->save();

    return response()->json([
        'message' => 'Now user is not verified',
        'user' => $user,
    ], 200);
}

    
    public function logout()
    {
        $user = Admin::where('email', auth()->user()->email)->first();
        $user->tokens()->delete();

        return response([
            'message' => 'Logged out',
        ], 200);
    }

    public function getUser()
    {
        $users = User::all();
        $user = User::all();

        return response(['status' => 'success',
            'message' => 'User fetched successfully',
            'user' => $user,
        ], 200);
    }

    public function getOrder()
    {
        $admin = Auth::guard('admin')->user();
        $order = Order::all();

        return response(
            [
                'status' => 'success',
                'message' => 'Order fetched by '.$admin->username,
                'order' => $order,
            ],
            200
        );
    }
}
