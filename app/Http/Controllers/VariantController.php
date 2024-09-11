<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Variant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class VariantController extends Controller
{
    public function index()
    {
        $variants = Variant::orderBy('created_at', 'desc')->get(); // Apply orderBy to the query builder, not the collection
        return response()->json(['data' => $variants], 200);
    }


    public function store(Request $request)
    {

        $existingCategories = Variant::pluck('category')->toArray();
        
        if (!in_array($request->category, $existingCategories)) {
            $existingCategories[] = $request->category; // Tambahkan kategori baru ke daftar
        }
        
        $validator = Validator::make($request->all(), [
            'name_varian' => 'required|string|max:255',
            'category' => ['required', 'string', 'max:255', Rule::in($existingCategories)],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'stock_varian' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->all();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('variant'), $imageName);
            $data['image'] = $imageName;
        }

        $variant = Variant::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Variant created successfully',
            'data' => $variant
        ], 201);
    }

    public function show($id)
    {
        $variant = Variant::findOrFail($id);
        return response()->json(['data' => $variant], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name_varian' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:255',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'stock_varian' => 'sometimes|required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $variant = Variant::findOrFail($id);
        $data = $request->all();

        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($variant->image) {
                Storage::delete('variant/' . $variant->image);
            }

            // Simpan gambar baru
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('variant'), $imageName);
            $data['image'] = $imageName;
        }

        $variant->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Variant updated successfully',
            'data' => $variant
        ], 200);
    }

    public function disableVariant($id)
     {
        $post = Variant::find($id);
        $post->status_variant = false;
        $post->save();

        return response()->json(['message' => 'Topping disabled successfully'], 200);
     }

    public function enableVariant($id)
        {
            $post = Variant::find($id);
            $post->status_variant = true;
            $post->save();
    
            return response()->json(['message' => 'Menu enabled successfully'], 200);
        }

    public function destroy($id)
    {
        $variant = Variant::findOrFail($id);
        Storage::delete('public/variant/' . basename($variant->image));
        $variant->delete();

        return response()->json(['message' => 'Variant deleted successfully'], 204);
    }
}
