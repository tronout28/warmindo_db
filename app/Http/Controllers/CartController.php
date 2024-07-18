<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $cart = Cart::with('menu')->where('user_id', $request->user_id)->get();

        return response()->json([
            'success' => true,
            'data' => $cart,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'menuID' => 'required|exists:menus,menuID',
            'quantity' => 'required|integer|min:1',
        ]);

        $existingCart = Cart::where('user_id', $request->user_id)
            ->where('menuID', $request->menuID)
            ->first();

        if ($existingCart) {
            $existingCart->quantity += $request->quantity;
            $existingCart->save();

            return response()->json([
                'success' => true,
                'message' => 'Cart item quantity updated successfully',
                'data' => $existingCart,
            ]);
        } else {
            $cart = Cart::create([
                'user_id' => $request->user_id,
                'menuID' => $request->menuID,
                'quantity' => $request->quantity,
                'date_item_menu' => now(), // tambahkan field ini jika perlu
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart successfully',
                'data' => $cart,
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        // Mengambil semua data dari request
        $data = $request->json()->all();
    
        // Validasi data
        $validator = Validator::make($data, [
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required|integer|min:1',
        ]);
    
        // Jika validasi gagal, kembalikan respon dengan error
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Cari cart berdasarkan user_id dan id cart
        $cart = Cart::where('user_id', $data['user_id'])->find($id);
    
        // Jika cart tidak ditemukan, kembalikan respon dengan pesan error
        if (!$cart) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }
    
        // Update quantity cart
        $cart->update(['quantity' => $data['quantity']]);
    
        // Kembalikan respon sukses
        return response()->json([
            'success' => true,
            'message' => 'Cart item updated successfully',
            'data' => $cart,
        ]);
    }
    

    public function destroy($id)
    {
        $cart = Cart::find($id);

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found',
            ], 404);
        }

        $cart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart deleted successfully',
        ], 200);
    }
}
