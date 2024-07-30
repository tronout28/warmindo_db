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
        if ($user == null || !Hash::check($request->password, $user->password)) {
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
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'profile_picture' => 'nullable|string|max:255', 
            'email' => 'required|email',
            'phone_number' => 'required|string',
            'password' => 'required|string|min:8',
        ]);

        $admin = Admin::where('email', $request->email)->first();
        if ($admin != null) {
            return response([
                'message' => 'Email already exists',
            ], 409);
        }

        $admindata = [
            'name' => $request->username,
            'username' => $request->username,
            'profile_picture' => $request->profile_picture, 
            'email' => $request->email,
            'phone_number' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'admin',
        ];
        $admin = Admin::create($admindata);
        $token = $admin->createToken('warmindo')->plainTextToken;

        return response(['admin' => $admin,
            'token' => $token,
        ], 201);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'profile_picture' => 'nullable|string|max:255',
            'phone' => 'nullable|string',
            'password' => 'nullable|string|min:8',
            'current_password' => 'sometimes|nullable|string|min:8', 
        ]);
        
        $user = Admin::where('email', $request->email)->first();
        if ($user == null) {
            return response([
                'message' => 'Email not found',
            ], 404);
        }

        $userdata = [
            'username' => $request->username,
            'name' => $request->name,
            'profile_picture' => $request->profile_picture,
            'email' => $request->email,
            'phone_number' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'admin',
        ];
        $user->update($userdata);
        $token = $user->createToken('warmindo')->plainTextToken;

        if ($request->hasFile('profile_picture')) {
            // Delete old picture if exists
            if ($user->profile_picture) {
                $oldImagePath = public_path('images') . '/' . $user->profile_picture;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            $imageName = env('APP_URL') . time().'.'.$request->profile_picture->extension();
            Admin::where('Uploading picture profile: '.$imageName);
            $request->profile_picture->move(public_path('images'), $imageName);
            $user->profile_picture = $imageName;
        }

        // Validate current_password and update password if valid
        if ($request->filled('current_password') || $request->filled('password')) {
            $request->validate([
                'current_password' => 'required|string|min:8',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect',
                ], 400);
            }

            $user->password = Hash::make($request->password);
        }

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
