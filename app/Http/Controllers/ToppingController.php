<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ToppingController extends Controller
{
    public function index()
    {
        $toppings = Topping::all();

        return new PostResource(true, 'List Data Toppings', $toppings);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_topping' => 'required|string|max:255',
            'price' => 'required|numeric',
            'image' => 'required|string|max:255',
            'stock' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $topping = Topping::create($request->all());

        return new PostResource(true, 'Topping Berhasil Ditambahkan!', $topping);
    }

    public function show($id)
    {
        $topping = Topping::find($id);

        if (is_null($topping)) {
            return response()->json(['message' => 'Topping not found'], 404);
        }

        return new PostResource(true, 'Detail Data Topping', $topping);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name_topping' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric',
            'image' => 'sometimes|required|string|max:255',
            'stock' => 'sometimes|required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $topping = Topping::find($id);

        if (is_null($topping)) {
            return response()->json(['message' => 'Topping not found'], 404);
        }

        $topping->update($request->all());

        return new PostResource(true, 'Topping Berhasil Diubah!', $topping);
    }

    public function destroy($id)
    {
        $topping = Topping::find($id);

        if (is_null($topping)) {
            return response()->json(['message' => 'Topping not found'], 404);
        }

        $topping->delete();

        return new PostResource(true, 'Topping Berhasil Dihapus!', null);
    }
}
