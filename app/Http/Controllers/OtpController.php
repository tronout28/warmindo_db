<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OtpController extends Controller
{
    public function sendOtp(Request $request)
    {
        $user = auth()->user();
        $phone = '+62'.substr($user->phone_number, 1);
        $otp = rand(100000, 999999);
        $otps = Otp::where('user_id', $user->id)->first();
        $expiredOtps = Otp::where('created_at', '<=', Carbon::now()->subMinutes(5))->delete();
        if ($otps != null) {
            return response([
                'status' => 'failed',
                'message' => 'Try Again After 5 Minutes',
            ]);
        } 
    
        Http::post('https://wapiiiiiii-957b9f860ed5.herokuapp.com/message/', [
            'phoneNumber' => $phone,
            'message' => 'Halo '.$user->username.', '.$otp.' adalah kode OTP Anda. Demi Keamanan jangan berikan kode ini kepada siapapun.',
        ]);
        Otp::create([
            'otp' => $otp,
            'user_id' => $user->id,
        ]);

        return response([
            'status' => 'success',
            'message' => 'OTP sent successfully',
        ], 200);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|min:6|max:6',
        ]);

        $user = User::where('id', auth()->user()->id)->first();
        $otp = $request->otp;
        $otps = Otp::where('user_id', $user->id)->first();

        if ($otp == $otps->otp) {
            $otps->delete();
            $user->phone_verified_at = Carbon::now();
            $user->save();

            return response([
                'status' => 'success',
                'message' => 'OTP verified successfully',
            ], 200);
        } else {
            return response([
                'status' => 'failed',
                'message' => 'OTP verification failed',
            ], 200);
        }
    }
}