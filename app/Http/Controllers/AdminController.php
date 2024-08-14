<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
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
            'phone_number' => 'nullable|string',
            'password' => 'required|string|min:8',
        ]);

        $admin = Admin::where('email', $request->email)->first();
        if ($admin != null) {
            return response([
                'message' => 'Email already exists',
            ], 409);
        }

        $admindata = [
            'name' => $request->name,
            'username' => $request->username,
            'profile_picture' => $request->profile_picture,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role' => 'admin',
        ];

        $admin = Admin::create($admindata);
        $token = $admin->createToken('warmindo')->plainTextToken;

        return response([
            'admin' => $admin,
            'token' => $token,
        ], 201);
    }
    public function details()
    {
        $user = auth()->user();
        $token = $user->currentAccessToken();
        return response()->json([
            'success' => true,
            'message' => 'User details',
            'user' => $user,
            'token' => $token,
        ], 200);
    }
        public function update(Request $request)
        {
            $admin = auth()->user();
            // Validate request input
            $request->validate([
                'name' => 'nullable|string|max:255',
                'username' => 'nullable|string|max:255',
                'email' => 'nullable|email', // Email is nullable
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'phone_number' => 'nullable|string',
                'password' => 'nullable|string|min:8',
                'current_password' => 'nullable|string|min:8', // Make current_password nullable
            ]);
        
            // Find the admin by ID
        
        
            if ($admin == null) {
                return response([
                    'message' => 'Admin not found',
                ], 404);
            }
        
            // Prepare the user data for update
            $userdata = [];
            if ($request->filled('username')) {
                $userdata['username'] = $request->username;
            }
            if ($request->filled('name')) {
                $userdata['name'] = $request->name;
            }
            if ($request->filled('profile_picture')) {
                $userdata['profile_picture'] = $request->profile_picture;
            }
            if ($request->filled('email')) {
                $userdata['email'] = $request->email;
            }
            if ($request->filled('phone_number')) {
                $userdata['phone_number'] = $request->phone_number;
            }
        
            // Validate and update password if current password is provided
            if ($request->filled('password')) {
                if (!$request->filled('current_password') || !Hash::check($request->current_password, $admin->password)) {
                    return response([
                        'message' => 'Current password is incorrect or not provided',
                    ], 400);
                }
                $userdata['password'] = Hash::make($request->password);
            }
        
            // Update the user data
            $admin->update($userdata);
        
            // Process profile picture upload if provided
            if ($request->hasFile('profile_picture')) {
                // Delete old picture if exists
                if ($admin->profile_picture) {
                    $oldImagePath = public_path('images') . '/' . $admin->profile_picture;
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $imageName = env('APP_URL') . time().'.'.$request->profile_picture->extension();
            
                $request->profile_picture->move(public_path('images'), $imageName);
                $admin->profile_picture = $imageName;
                $admin->save();
            }
        
            // Generate a new token for the user
            $token = $admin->createToken('warmindo')->plainTextToken;
        
            return response([
                'admin' => $admin,
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

    public function getOrders()
    {
        $orders = Order::with(['orderDetails.menu'])->get();
        return response(['status' => 'success',
            'message' => 'Orders fetched successfully',
            'orders' => $orders,
        ], 200);
    }

    public function getOrderDetail($id)
    {
        $order = Order::with(['orderDetails.menu'])->find($id);

        if (is_null($order)) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order details retrieved successfully',
            'data' => $order,
        ], 200);
    }
}
