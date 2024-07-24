<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Variant;
use Illuminate\Support\Facades\Validator;

class VariantController extends Controller
{
    public function index()
    {
        $variants = Variant::all();
        return response()->json(['data' => $variants], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_varian' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'stock_varian' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

         $image = $request->file('variant');
         $imageName = time().'.'.$image->extension();
         $image->move(public_path('variant'), $imageName);

        $variant = Variant::create($request->all());

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

        if ($request->hasFile('variant')) {
            $image = $request->file('variant');
             $imageName = time().'.'.$image->extension();
             $image->move(public_path('variant'), $imageName);
 
             Storage::delete('public/variant/'.basename($post->image));
        }

        $variant->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Variant updated successfully',
            'data' => $variant
        ], 200);
    }

    public function destroy($id)
    {
        $variant = Variant::findOrFail($id);
        $variant->delete();

        return response()->json(['message' => 'Variant deleted successfully'], 204);
    }
}

