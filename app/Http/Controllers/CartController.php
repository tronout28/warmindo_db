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
            'menu_id' => 'required|exists:menus,menu_id',
            'quantity' => 'required|integer|min:1',
        ]);

        $existingCart = Cart::where('user_id', $request->user_id)
            ->where('menu_id', $request->menu_id)
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
                'menu_id' => $request->menu_id,
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
        // Debug request data
         Cart::where('Request data:', $request->all());

        // Validasi input request
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // Cari cart item berdasarkan user_id dan id
        $cart = Cart::where('user_id', $request->user_id)->find($id);

        // Jika cart item tidak ditemukan, kembalikan response 404
        if (!$cart) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        // Update quantity cart item
        $cart->update(['quantity' => $request->quantity]);

        // Kembalikan response berhasil
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
