<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\Topping;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ToppingController extends Controller
{
    public function index(Request $request)
    {
        $menuId = $request->query('menu_id');

        if ($menuId) {
            $toppings = Menu::findOrFail($menuId)->toppings->map(function ($topping) use ($menuId) {
                return [
                    'id' => $topping->id,
                    'name' => $topping->name_topping,
                    'price' => $topping->price,
                    'stock' => $topping->stock_topping,
                    'status' => $topping->status_topping,
                    'menus' => [
                        [
                            'menu_id' => $menuId,
                            'menu_name' => Menu::find($menuId)->name_menu,
                        ]
                    ],
                    // tambahkan properti lain yang dibutuhkan
                ];
            });
        } else {
            $toppings = Topping::with('menus')->get()->map(function ($topping) {
                return [
                    'id' => $topping->id,
                    'name_topping' => $topping->name_topping,
                    'price' => $topping->price,
                    'stock' => $topping->stock_topping,
                    'status' => $topping->status_topping,
                    'menus' => $topping->menus->map(function ($menu) {
                        return [
                            'menu_id' => $menu->id,
                            'menu_name' => $menu->name_menu,
                        ];
                    }),
                    // tambahkan properti lain yang dibutuhkan
                ];
            });
        }

        return response()->json(['data' => $toppings], 200);
    }



    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_topping' => 'required|string|max:255',
            'stock_topping' => 'required|integer',
            'price' => 'required|numeric',
            'status_topping' => 'required|boolean',
            'menu_ids' => 'required|array',
            'menu_ids.*' => 'integer|exists:menus,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
    
        $topping = Topping::create($request->except('menu_ids'));
    
        // Attach the topping to the selected menus
        $topping->menus()->attach($request->menu_ids);
    
        return response()->json([
            'success' => true,
            'message' => 'Topping created and associated with menus successfully',
            'data' => $topping
        ], 201);
    }

    public function show(Request $request)
    {
        // $request->validate([
        //     'menu_id' => 'required|integer|exists:menus,id',
        // ]);
        $topping = Topping::where('id', $request->topping_id)->first();
        return response()->json([
            'status' => 'success',
            'data' => $topping
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name_topping' => 'sometimes|nullable|string|max:255',
            'stock_topping' => 'sometimes|nullable|integer',
            'price' => 'sometimes|nullable|numeric',
            'status_topping' => 'sometimes|nullable|boolean',
            'menu_ids' => 'sometimes|nullable|array',
            'menu_ids.*' => 'integer|exists:menus,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
    
        $topping = Topping::findOrFail($id);
        $topping->update($request->except('menu_ids'));
    
        if ($request->has('menu_ids')) {
            // Sync the topping with the selected menus
            $topping->menus()->sync($request->menu_ids);
        }

        // if ($request->hasFile('image')) {
        //     // Hapus gambar lama jika ada
        //     if ($topping->image) {
        //         Storage::delete('topping/' . $topping->image);
        //     }

        //     // Simpan gambar baru
        //     $image = $request->file('image');
        //     $imageName = time() . '.' . $image->extension();
        //     $image->move(public_path('topping'), $imageName);
        //     $data['image'] = $imageName;
        // }

        // $topping->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Topping updated successfully',
            'data' => $topping
        ], 200);
    }

    public function disableTopping($id)
     {
        $post = Topping::find($id);
        $post->status_topping = false;
        $post->save();

        return response()->json(['message' => 'Topping disabled successfully'], 200);
     }

    public function enableTopping($id)
        {
            $post = Topping::find($id);
            $post->status_topping = true;
            $post->save();
    
            return response()->json(['message' => 'Menu enabled successfully'], 200);
        }

    public function destroy($id)
    {
        $topping = Topping::findOrFail($id);
        Storage::delete('public/topping/' . basename($topping->image));
        $topping->delete();

        return response()->json(['message' => 'Topping deleted successfully'], 204);
    }
}
