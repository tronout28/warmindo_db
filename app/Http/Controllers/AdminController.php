<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Http\Resources\OrderDetailResource;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Services\FirebaseService;
use App\Models\Otp;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;

class AdminController extends Controller
{

    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'notification_token' => 'nullable|string',
        ]);
    
        // Find the admin by email
        $user = Admin::where('email', $request->email)->first();
    
        if ($user == null || !Hash::check($request->password, $user->password)) {
            return response([
                'message' => 'Invalid credentials',
            ], 401);
        }
    
        // Update the notification token if provided
        if ($request->filled('notification_token')) {
            $user->notification_token = $request->notification_token;
            $user->save();
        }
    
        // Generate the token
        $token = $user->createToken('warmindo')->plainTextToken;
    
        return response([
            'admin' => $user,
            'token' => $token,
        ], 200);
    }
    

    public function detailadmin()
{
    $admin = auth()->user();

    if (!$admin) {
        return response()->json([
            'message' => 'Admin not found',
        ], 404);
    }

    return response()->json([
        'message' => 'Admin details',
        'data' => $admin,
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
            'notification_token' => 'nullable|string',

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

    public function update(Request $request)
    {
        // Validate request input
        $request->validate([
            'name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'email' => 'nullable|email', // Email is nullable
            'profile_picture' => 'nullable|image|max:255', // Validate as image and limit file size
            'phone_number' => 'nullable|string',
            'password' => 'nullable|string|min:8',
            'current_password' => 'nullable|string|min:8', // Make current_password nullable
        ]);
    
        // Get the authenticated user
        $user = $request->user();
    
        if ($user == null) {
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
        if ($request->filled('email')) {
            $userdata['email'] = $request->email;
        }
        if ($request->filled('phone_number')) {
            $userdata['phone_number'] = $request->phone_number;
        }
    
        // Validate and update password if current password is provided
        if ($request->filled('password')) {
            if (!$request->filled('current_password') || !Hash::check($request->current_password, $user->password)) {
                return response([
                    'message' => 'Current password is incorrect or not provided',
                ], 400);
            }
            $userdata['password'] = Hash::make($request->password);
        }
    
        // Update the user data
        $user->update($userdata);
    
        // Process profile picture upload if provided
        if ($request->hasFile('profile_picture')) {
            $profilePicture = $request->file('profile_picture');
            $profilePictureName = time() . '.' . $profilePicture->extension();
            $profilePicture->move(public_path('images'), $profilePictureName);
            $user->profile_picture = $profilePictureName;
            $user->save();
        }
    
        // Generate a new token for the user
        $token = $user->createToken('warmindo')->plainTextToken;
    
        return response([
            'admin' => $user,
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

    public function rejectcancel($id)
    {
        $order = Order::where('id', $id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Update the order status to "sedang diproses"
        $order->status = 'sedang diproses';
        $order->save();

        // Send notification to the user about the rejection
        $this->firebaseService->sendNotification(
            $order->user->notification_token,
            'Pembatalan Ditolak',
            'Permintaan pembatalan order Anda dengan ID ' . $order->id . ' telah ditolak. Pesanan Anda sedang diproses.',
            ''
        );

        // Send notification to the admin if required
        $this->firebaseService->sendNotification(
            $order->admin->notification_token,
            'Pembatalan Ditolak',
            'Permintaan pembatalan order dari ' . $order->user()->name . ' telah ditolak. Pesanan sedang diproses.',
            ''
        );

        return response()->json([
            'success' => true,
            'message' => 'Order cancelation rejected successfully',
            'data' => $order->load(['orderDetails.menu']),
        ], 200);
    }

    public function acceptcancel($id)
    {
        $order = Order::where('id', $id)->first();
        $order2 = Order::where('id', $id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Update the order status to "batal"
        $order->status = 'menunggu pengembalian dana';
        $order->save();

        // Send notification to the user about the acceptance
        if($order2->status == 'sedang diproses'){
            $this->firebaseService->sendNotification(
                $order->user->notification_token,
                'Pesanan Dibatalkan',
                'Pesanan anda dengan ID ' . $order->id . ' telah dibatalkan. oleh admin silahkan mengambil uang refund di lokasi order.',
                ''
            );
    }else{
        $this->firebaseService->sendNotification(
            $order->user->notification_token,
            'Pesanan Dibatalkan',
            'Permintaan pembatalan order Anda dengan ID ' . $order->id . ' telah dibatalkan. Pesanan Anda telah dibatalkan.',
            ''
        );
    }
        // Retrieve the first admin
    $admin = Admin::where('id')->first();

    // Send notification to the admin if required
    $this->firebaseService->sendNotification(
        $admin->notification_token,
        'Pembatalan Diterima',
        'Permintaan pembatalan order dari ' . $order->user->name . ' telah diterima. Pesanan telah dibatalkan.',
        ''
    );

        return response()->json([
            'success' => true,
            'message' => 'Order cancelation accepted successfully',
            'data' => $order->load(['orderDetails.menu']),
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
        // Define the query
        $query = Order::with(['orderDetails.menu', 'user']) // Ensure the user relationship is loaded
            ->leftJoin('transactions', 'orders.id', '=', 'transactions.order_id')
            ->select('orders.*', 'transactions.payment_channel as transaction_payment_method');

        // Log the SQL query
        Log::info('SQL Query:', [
            'query' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        // Execute the query and get the results
        $orders = $query->get();

        // Log the raw data
        Log::info('Orders Data:', ['orders' => $orders]);

        if ($orders->isNotEmpty()) {
            // Fetch all admins (you can modify this to fetch specific admins if necessary)
            $admins = Admin::all();

            foreach ($orders as $order) {
                foreach ($admins as $admin) {
                    $this->firebaseService->sendToAdmin(
                        $admin->notification_token,
                        'Ada pesanan baru!',
                        'Pesanan dari ' . $order->user->username . ' telah diterima. Silahkan cek aplikasi Anda. Terima kasih! 🎉',
                        '',
                        [] // Pass an array instead of a string or other data types
                    );
                }
            }
        }

        return response([
            'status' => 'success',
            'message' => 'Orders fetched successfully',
            'orders' => $orders->map(function($order) {
                // Determine the payment method to return
                $paymentMethod = $order->transaction_payment_method ?? $order->payment_method;
                // Map the order details to the OrderDetailResource
                $orderDetails = OrderDetailResource::collection($order->orderDetails);
                return [
                    'id' => $order->id,
                    'user_id' => $order->user_id,
                    'price_order' => $order->price_order,
                    'status' => $order->status,
                    'note' => $order->note,
                    'payment_method' => $paymentMethod, // Use the resolved payment method
                    'order_method' => $order->order_method,
                    'cancel_method' => $order->cancel_method,
                    'reason_cancel' => $order->reason_cancel,
                    'no_rekening' => $order->no_rekening,
                    'admin_fee' => $order->admin_fee,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                    'orderDetails' => $orderDetails,
                ];
            }),
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = Admin::where('email', $request->email)->first();
        if ($user == null) {
            return response([
                'status' => 'failed',
                'message' => 'User not found',
            ], 404);
        }
        $otp = rand(100000, 999999);
        $otps = Otp::where('admin_id', $user->id)->first();
        if ($otps != null) {
            return response([
                'status' => 'failed',   
                'message' => 'Otp already sent. Try again after 5 minutes',
            ]);
        }
        Otp::create([
            "otp" => $otp,
            "admin_id" => $user->id,
        ]);
        $description = 'Ini adalah kode verifiskasi anda untuk reset password akun anda di aplikasi Warmindo App. Jangan berikan kode ini kepada siapapun. Kode berlaku selama 5 menit';
        Mail::send('email.mail', ['otp' => $otp, "description" => $description, 'username' => $user->username], function ($message) use ($user) {
            $message->to($user->email, $user->username)->subject('OTP Verification');
        });
        return response([
            'status' => 'success',
            'message' => 'OTP sent to your email, check your email address',
        ]);
    }

    public function verifyForgotPassword(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|min:6|max:6',
            'email' => 'required|email',
        ]);

        $user = Admin::where('email', $request->email)->first();
        $otp = $request->otp;
        $otps = Otp::where('admin_id', $user->id)->first();

        if ($otps == null) {
            return response([
                'status' => 'failed',
                'message' => 'OTP not found',
            ], 404);
        }

        if ($otp == $otps->otp) {
            $otps->delete();

            // Generate a token for password reset
            $token = Str::random(60);
            $user->reset_token = $token;
            $user->save();

            return response([
                'status' => 'success',
                'message' => 'OTP verified successfully, use this token to reset your password',
                'token' => $token,
            ], 200);
        } else {
            return response([
                'status' => 'failed',
                'message' => 'OTP verification failed',
            ], 401);
        }
    }


    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
    
        $user = Admin::where('reset_token', $request->token)->first();
    
        if (!$user) {
            return response([
                'status' => 'failed',
                'message' => 'Invalid or expired token',
            ], 404);
        }
    
        // Update the password
        $user->password = bcrypt($request->password);
        // Clear the reset token after successful password reset
        $user->reset_token = null;
        $user->save();
    
        return response([
            'status' => 'success',
            'message' => 'Password has been reset successfully',
        ], 200);
    }
    


    public function userOrderdetail($id)
    {
        // Validate the ID directly instead of through the request
        if (!is_numeric($id) || !Order::where('id', $id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'The order id is invalid or does not exist.'
            ], 400);
        }
    
        $orderDetails = OrderDetail::where('order_id', $id)->get();
    
        return response()->json([
            'status' => 'success',
            'message' => 'List of order details',
            'data' => OrderDetailResource::collection($orderDetails)
        ], 200);
    }   
}
