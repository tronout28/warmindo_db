<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Topping;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

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

         $image = $request->file('topping');
         $imageName = time().'.'.$image->extension();
         $image->move(public_path('topping'), $imageName);

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

        if ($request->hasFile('topping')) {
            $image = $request->file('topping');
            $imageName = time().'.'.$image->extension();
            $image->move(public_path('topping'), $imageName);

            Storage::delete('public/topping/'.basename($post->image));

            $post->update([
                'image' => $imageName,
                'name_topping' => $request->name_menu,
                'price' => $request->price,
                'stock' => $request->stock,
            ]);
        } else {
            $post->update([
                'name_topping' => $request->name_menu,
                'price' => $request->price,
                'stock' => $request->stock,
            ]);
        }

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
