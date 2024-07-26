<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Topping;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

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
            'price' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'stock' => 'required|integer',
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
        $topping = Topping::find($id);

        if (is_null($topping)) {
            return response()->json(['message' => 'Topping not found'], 404);
        }

        return response()->json(['data' => $topping], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name_topping' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'stock' => 'sometimes|required|integer',
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
        $topping = Topping::find($id);

        if (is_null($topping)) {
            return response()->json(['message' => 'Topping not found'], 404);
        }

        if ($topping->image) {
            Storage::delete('topping/' . $topping->image);
        }
        
        
        $topping->delete();

        return response()->json(['message' => 'Topping deleted successfully'], 204);
    }
}
