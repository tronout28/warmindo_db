<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AlamatUser;

class AlamatUserController extends Controller
{
    public function index()
    {
        $userId = auth()->user()->id;

        if ($userId) {
            $alamatUser = AlamatUser::where('user_id', $userId)
                ->orderBy('created_at', 'desc') // Urutkan alamat_user berdasarkan created_at
                ->get()
                ->map(function ($alamatUser) {
                    return [
                        'id' => $alamatUser->id,
                        'nama_alamat' => $alamatUser->nama_alamat,
                        'nama_kost' => $alamatUser->nama_kost,
                        'catatan_alamat' => $alamatUser->catatan_alamat,
                        'detail_alamat' => $alamatUser->detail_alamat,
                        'is_selected' => $alamatUser->is_selected,
                        'latitude' => $alamatUser->latitude,
                        'longitude' => $alamatUser->longitude,
                        'radius_km' => $alamatUser->radius_km,
                        'user' => [
                            'user_id' => $alamatUser->user_id,
                            'name' => $alamatUser->user->name,
                        ],
                        // tambahkan properti lain yang dibutuhkan
                    ];
                });
        } else {
            $alamatUser = AlamatUser::with(['user'])
                ->orderBy('created_at', 'desc') // Urutkan alamat_user berdasarkan created_at
                ->get()
                ->map(function ($alamatUser) {
                    return [
                        'id' => $alamatUser->id,
                        'nama_alamat' => $alamatUser->nama_alamat,
                        'nama_kost' => $alamatUser->nama_kost,
                        'catatan_alamat' => $alamatUser->catatan_alamat,
                        'detail_alamat' => $alamatUser->detail_alamat,
                        'is_selected' => $alamatUser->is_selected,
                        'latitude' => $alamatUser->latitude,
                        'longitude' => $alamatUser->longitude,
                        'radius_km' => $alamatUser->radius_km,
                        'user' => [
                            'user_id' => $alamatUser->user_id,
                            'name' => $alamatUser->user->name,
                        ],
                        // tambahkan properti lain yang dibutuhkan
                    ];
                });
        }

        return response()->json([
            'status' => 'success',
            'data' => $alamatUser
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_alamat' => 'required|string|max:255',
            'nama_kost' => 'nullable|string|max:255',
            'detail_alamat' => 'required|string',
            'catatan_alamat' => 'required|string',
            'longitude' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'radius_km' => 'nullable|numeric',
        ]);

        $user = auth()->user();

        $alamatUser = AlamatUser::create([
            'nama_alamat' => $request->nama_alamat,
            'nama_kost' => $request->nama_kost,
            'detail_alamat' => $request->detail_alamat,
            'catatan_alamat' => $request->catatan_alamat,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'radius_km' => $request->radius_km,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Alamat user created successfully',
            'data' => $alamatUser
        ], 201);
    }

    public function show($id)
    {
        $alamatUser = AlamatUser::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $alamatUser
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_alamat' => 'sometimes|nullable|string|max:255',
            'nama_kost' => 'sometimes|nullable|string|max:255',
            'detail_alamat' => 'sometimes|nullable|string',
            'catatan_alamat' => 'sometimes|nullable|string',
            'latitude' => 'sometimes|nullable|numeric',
            'longitude' => 'sometimes|nullable|numeric',
        ]);

        $alamatUser = AlamatUser::findOrFail($id);

        $alamatUser->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Alamat user updated successfully',
            'data' => $alamatUser
        ]);
    }

    public function destroy($id)
    {
        $alamatUser = AlamatUser::findOrFail($id);
        $alamatUser->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Alamat user deleted successfully'
        ]);
    }

    public function enableisSelected($id){
        $user = auth()->user();
        
        // Nonaktifkan semua alamat user lain
        AlamatUser::where('user_id', $user->id)
            ->where('id', '!=', $id) // Pastikan alamat lain
            ->update(['is_selected' => false]);
    
        // Aktifkan alamat yang dipilih
        $alamatUser = AlamatUser::findOrFail($id);
        $alamatUser->is_selected = true;
        $alamatUser->save();
    
        return response()->json([
            'status' => 'success',
            'message' => 'Alamat user enabled successfully'
        ]);
    }
    

    public function disableisSelected($id){
        $alamatUser = AlamatUser::findOrFail($id);
        $alamatUser->is_selected = false;
        $alamatUser->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Alamat user disabled successfully'
        ]);
    }

}
