<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Otp;
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
            
        ]);

        $user = User::where('google_id', $validatedData['google_id'])
                    ->orWhere('email', $validatedData['email'])
                    ->first();

        if ($user) {
            // Update user information if needed
            $user->update([
                'username' => $validatedData['name'],
                'profile_picture' => $validatedData['profile_picture'],
                'email' => $validatedData['email'],
                'google_id' => $validatedData['google_id'],
                'email_verified_at' => now(),
            ]);
        } else {
            // Create new user
            $user = User::create([
                'name' => $validatedData['name'],
                'username' => $validatedData['name'],
                'profile_picture' => $validatedData['profile_picture'],
                'email' => $validatedData['email'],
                'google_id' => $validatedData['google_id'],
                'email_verified_at' => now(),
                'password' => null, 
            ]);
        }

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
            'picture_profile' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
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
        if ($request->hasFile('picture_profile')) {
            $imageName = time().'.'.$request->picture_profile->extension();
            $request->picture_profile->move(public_path('images'), $imageName);
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
            'picture_profile' => $imageName,
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

        return response()->json([
            'success' => true,
            'message' => 'User login successfully',
            'user' => $user,
            'token' => $token,
        ], 200);
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
        $user = auth()->user();

        $request->validate([
            'name' => 'sometimes|nullable|string|max:255',
            'username' => 'sometimes|nullable|string|max:255|unique:users,username,'.$user->id,
            'phone_number' => 'sometimes|nullable|string|max:255|unique:users,phone_number,'.$user->id,
            'otp' => 'required_if:phone_number,true|string|min:6|max:6', // Validasi OTP jika phone_number diubah
            'email' => 'sometimes|nullable|email|unique:users,email,'.$user->id,
            'current_password' => 'sometimes|nullable|string|min:8', // Validasi current_password
            'password' => 'sometimes|nullable|string|min:8|confirmed', // Validasi konfirmasi password baru
            'picture_profile' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        Log::info('Update User Request: ', $request->all());

        if ($request->filled('phone_number')) {
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

        if ($request->hasFile('picture_profile')) {
            // Delete old picture if exists
            if ($user->picture_profile) {
                $oldImagePath = public_path('images') . '/' . $user->picture_profile;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            $imageName = time().'.'.$request->picture_profile->extension();
            Log::info('Uploading picture profile: '.$imageName);
            $request->picture_profile->move(public_path('images'), $imageName);
            $user->picture_profile = $imageName;
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

        Log::info('User updated: ', $user->toArray());

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'user' => $user,
        ], 200);
    }

    public function updatePhoneNumberForGoogle(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'phone_number' => 'sometimes|nullable|string|max:255|unique:users,phone_number,'.$user->id,
        ]);

        Log::info('Update User Request: ', $request->all());

        $user->phone_number = $request->phone_number;
        $user->save();
    

        Log::info('User updated: ', $user->toArray());

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
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
}
