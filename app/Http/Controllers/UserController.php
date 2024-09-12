<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Order;
use App\Models\Variant;
use App\Models\Otp;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\ToppingResource;
use App\Models\OrderDetailTopping;
use App\Models\AlamatUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator; // Correct import
use Symfony\Component\HttpFoundation\Response;



class UserController extends Controller
{
    public function googleLogin(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'google_id' => 'required|string',
            'profile_picture' => 'nullable|string',
            'notification_token' => 'required|string',
        ]);
    
        // Find the user by Google ID or email
        $user = User::where('google_id', $validatedData['google_id'])
                    ->orWhere('email', $validatedData['email'])
                    ->first();
    
        if ($user) {
            // Update existing user details including the notification token
            $user->update([
                'notification_token' => $validatedData['notification_token'],
            ]);
            $user->save();
        } else {
            // Create a new user
            $user = User::create([
                'name' => $validatedData['name'],
                'username' => $validatedData['name'],
                'profile_picture' => $validatedData['profile_picture'],
                'email' => $validatedData['email'],
                'notification_token' => $validatedData['notification_token'],
                'google_id' => $validatedData['google_id'],
                'email_verified_at' => now(),
                'password' => null, 
            ]);
            $user->save();
        }
    
        // Generate the token
        $token = $user->createToken('warmindo')->plainTextToken;
        
    
        return response()->json([
            'success' => true,
            'message' => 'User logged in successfully',
            'user' => $user,
            'token' => $token,
        ], 200);
    }
    

    public function register(Request $request)
    {
        // Custom validation logic
       $messages = [
            'name.required' => 'name dibutuhkan.',
            'username.required' => 'username dibutuhkan',
            'username.unique' => 'username sudah ada.',
            'phone_number.required' => 'nomor hp dibutuhkan.',
            'phone_number.unique' => 'phone number sudah ada.',
            'email.required' => 'email dibutuhkan.',
            'email.unique' => 'email sudah ada.',
            'password.required' => 'password dibutuhkan.',
            'password.min' => 'password minimal 8 karakter',
        ];

        // Custom validation logic
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'phone_number' => 'required|string|max:255|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10000',
            'notification_token' => 'nullable|string',
        ], $messages);
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    
        // Handle file upload
        $imageName = "";
        if ($request->hasFile('profile_picture')) {
            $imageName = time() . '.' . $request->profile_picture->extension();
            $request->profile_picture->move(public_path('images'), $imageName);
        } else {
            $imageName = null;
        }
    
        // Create the user
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'notification_token' => $request->notification_token,
            'user_verified' => false,
        ]);
    
        // Create a token for the user
        $token = $user->createToken('warmindo')->plainTextToken;
    
    

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user,
            'token' => $token,
        ], Response::HTTP_CREATED);
    }




    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->login)
            ->orWhere('username', $request->login)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        $token = $user->createToken('warmindo')->plainTextToken;
        $user->notification_token = $request->notification_token;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User login successfully',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    
    public function forgotPassword(Request $request)
    {
        $user = auth()->user();
        $user = User::where('id', $user->id)->first();

        $request->validate([
            'new_password' => 'required|string|confirmed|min:8'
        ]);
    
        
        // Update password if OTP verification is successful
        $user->password = Hash::make($request->new_password);
        $user->save();
    
        return response()->json([
            'success' => true,
            'message' => 'Berhasil Di reset',
        ], 200);
    }
    


    public function details()
    {
        $user = auth()->user();
        $user = User::where('id', $user->id)->first();
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
        $auth = auth()->user();
        $user = User::where('id', $auth->id)->first();

        $request->validate([
            'name' => 'sometimes|nullable|string|max:255',
            'username' => 'sometimes|nullable|string|max:255|unique:users,username,'.$user->id,
            'phone_number' => 'sometimes|nullable|string|max:255|unique:users,phone_number,'.$user->id,
            'otp' => 'required_if:phone_number,true|string|min:6|max:6', // Validasi OTP jika phone_number diubah
            'email' => 'sometimes|nullable|email|unique:users,email,'.$user->id,
            'current_password' => 'sometimes|nullable|string|min:8', // Validasi current_password
            'password' => 'sometimes|nullable|string|min:8|confirmed', // Validasi konfirmasi password baru\
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10000',
        ]);

        Log::info('Update User Request: ', $request->all());
 
        if ($request->filled('phone_number')) {
            if($user->phone_verified_at != null){
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor Hp telah terverifikasi',
                ], 422);
            }
            $otp = Otp::where('user_id', $user->id)->where('otp', $request->otp)->first();
            if (!$otp || $otp->created_at->diffInMinutes(now()) > 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP',
                ], 400);
            }

            $user->phone_number = $request->phone_number;
            $user->phone_verified_at = Carbon::now();
            $otp->delete();
        }

        $user->name = $request->get('name', $user->name);
        $user->username = $request->get('username', $user->username);
        $user->email = $request->get('email', $user->email);

        if ($request->hasFile('profile_picture')) {
            // Delete old picture if exists
            if ($user->profile_picture) {
                $oldImagePath = public_path('images') . '/' . $user->profile_picture;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            $imageName = env('APP_URL') . time().'.'.$request->profile_picture->extension();
            Log::info('Uploading picture profile: '.$imageName);
            $request->profile_picture->move(public_path('images'), $imageName);
            $user->profile_picture = $imageName;
        }

        // Validasi current_password dan perbarui password jika valid
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
    
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'user' => $user,
        ], 200);
    }

  

    public function updatePhoneNumberForGoogle(Request $request)
    {
        $user = auth()->user();
        $user = User::where('id', $user->id)->first();

        $request->validate([
            'phone_number' => 'sometimes|nullable|string|max:255|unique:users,phone_number,'.$user->id,
        ]);

        Log::info('Update User Request: ', $request->all());

       
  
    $user->phone_number = $request->phone_number;
    $user->phone_verified_at = null;
    $user->save();
        Log::info('User updated: ', $user->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Nomor Hp berhasil diganti',
            'user' => $user,
        ], 200);
    }

    public function index()
    {
        $users = User::all();

        return response()->json([
            'success' => true,
            'message' => 'User list',
            'data' => $users,
        ], 200);
    }

    public function logout()
    {
        $user = User::where('email', auth()->user()->email)->first();
        $user->notification_token = null;
        $user->save();
        $user->tokens()->delete();

        return response([
            'message' => 'Logged out',
        ], 200);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if ($user) {
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'User not found',
        ], 404);
    }
    public function getHistory()
    {
        $user = auth()->user();

        // Define the query
        $query = Order::with(['orderDetails.menu', 'alamatUser']) // Ensure the alamatUser relationship is loaded
            ->where('user_id', $user->id)
            ->leftJoin('transactions', 'orders.id', '=', 'transactions.order_id')
            ->select('orders.*', 'transactions.payment_channel as transaction_payment_method');

        // Execute the query
        $orders = $query->get();

        return response([
            'status' => 'success',
            'message' => 'Orders fetched successfully',
            'orders' => $orders->map(function ($order) use ($user) {
                // Determine the payment method to return
                $paymentMethod = $order->transaction_payment_method ?? $order->payment_method;

                // Filter order details to include only the user's rating
                $orderDetails = $order->orderDetails->map(function ($detail) use ($user) {
                    $userRating = $detail->menu->ratings()
                        ->where('user_id', $user->id)
                        ->where('order_detail_id', $detail->id)
                        ->first();

                    return [
                        'id' => $detail->id,
                        'quantity' => $detail->quantity,
                        'user_rating' => $userRating ? $userRating->rating : null,
                        'variant_id' => $detail->variant_id,
                        'variant' => Variant::find($detail->variant_id),
                        'toppings' => ToppingResource::collection(OrderDetailTopping::where('order_detail_id', $detail->id)->get()),
                        'menu' => $detail->menu,
                    ];
                });

                // Add logic to get the active alamat user if order_method is delivery
                $alamatAktif = null;
                if ($order->order_method === 'delivery') {
                    $alamatAktif = AlamatUser::find($order->alamat_users_id);
                }

                return [
                    'id' => $order->id,
                    'user_id' => $order->user_id,
                    'price_order' => $order->price_order,
                    'status' => $order->status,
                    'note' => $order->note,
                    'payment_method' => $paymentMethod,
                    'cancel_method' => $order->cancel_method,
                    'reason_cancel' => $order->reason_cancel,
                    'no_rekening' => $order->no_rekening,
                    'admin_fee' => $order->admin_fee,
                    'order_method' => $order->order_method,
                    'created_at' => $order->created_at,
                    'driver_fee' => $order->driver_fee,
                    'updated_at' => $order->updated_at,
                    'orderDetails' => $orderDetails,
                    'alamat' => $alamatAktif ? [
                        'id' => $alamatAktif->id,
                        'nama_alamat' => $alamatAktif->nama_alamat,
                        'nama_kost' => $alamatAktif->nama_kost,
                        'catatan_alamat' => $alamatAktif->catatan_alamat,
                        'detail_alamat' => $alamatAktif->detail_alamat,
                        'latitude' => $alamatAktif->latitude,
                        'longitude' => $alamatAktif->longitude,
                    ] : null, // Include active alamat only if order method is delivery
                ];
            }),
        ], 200);
    }

    
}
