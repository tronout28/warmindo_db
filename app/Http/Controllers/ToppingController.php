<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Topping;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ToppingController extends Controller
{
    public function index()
    {
        $toppings = Topping::all();
        return response()->json(['data' => $toppings], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_topping' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'stock_topping' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->all();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('topping'), $imageName);
            $data['image'] = $imageName;
        }

        $topping = Topping::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Topping created successfully',
            'data' => $topping
        ], 201);
    }

    public function show($id)
    {
        $topping = Topping::findOrFail($id);
        return response()->json(['data' => $topping], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name_topping' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:255',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'stock_topping' => 'sometimes|required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $topping = Topping::findOrFail($id);
        $data = $request->all();

        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($topping->image) {
                Storage::delete('topping/' . $topping->image);
            }

            // Simpan gambar baru
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('topping'), $imageName);
            $data['image'] = $imageName;
        }

        $topping->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Topping updated successfully',
            'data' => $topping
        ], 200);
    }

    public function destroy($id)
    {
        $topping = Topping::findOrFail($id);
        Storage::delete('public/topping/' . basename($topping->image));
        $topping->delete();

        return response()->json(['message' => 'Topping deleted successfully'], 204);
    }
}
