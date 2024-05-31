<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = Cart::with('menu')->get();

        return response()->json([
            'success' => true,
            'data' => $carts,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'menuID' => 'required|exists:menus,menuID',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::create([
            'user_id' => $request->user_id,
            'menuID' => $request->menuID,
            'quantity' => $request->quantity,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart successfully',
            'data' => $cart,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::find($id);

        if (! $cart) {
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

        if (! $cart) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $cart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart item removed successfully',
        ]);
    }
}
