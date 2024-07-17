<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $cart = Cart::with('menu')->where('user_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'data' => $cart,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'menuID' => 'required|exists:menus,menuID',
            'quantity' => 'required|integer|min:1',
        ]);

        $existingCart = Cart::where('user_id', $user->id)
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
                'user_id' => $user->id,
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
        $user = auth()->user();

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::where('user_id', $user->id)->find($id);

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
        $user = auth()->user();

        $cart = Cart::where('user_id', $user->id)->find($id);

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
