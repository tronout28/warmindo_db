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
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::where('user_id', $request->user_id)->find($id);

        if (!$cart) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $cart->update(['quantity' => $request->quantity]);

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
