<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use App\Models\Variant;
use App\Http\Resources\CartResource;
use App\Models\CartTopping;
use App\Models\Menu;

class CartController extends Controller
{

    
    public function index()
    {
        $carts = Cart::with(['menu', 'cartToppings.topping'])->get();

        if ($carts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No carts found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => CartResource::collection($carts),
        ], 200);
    }

    public function createCart(Request $request)
    {
        
        $request->validate([
            'datas' => 'required|array',
            'datas.*.quantity' => 'required|integer',
            'datas.*.toppings' => 'nullable|array',
            'datas.*.toppings.*.topping_id' => 'required|integer|exists:toppings,id',
            'datas.*.toppings.*.quantity' => 'required|integer',
            'datas.*.menu_id' => 'required|integer|exists:menus,id',
            'datas.*.variant_id' => 'nullable|integer|exists:variants,id',
        ]);

        foreach ($request->datas as $data) {
            $carts = Cart::create([
                'quantity' => $data['quantity'],
                'menu_id' => $data['menu_id'],
                'variant_id' => $data['variant_id']?? null,
            ]);

            if ($data['toppings'] != null) {
                foreach ($data['toppings'] as $topping) {
                    CartTopping::create([
                        'cart_id' => $carts->id,
                        'topping_id' => $topping['topping_id'],
                        'quantity' => $topping['quantity'],
                    ]);
                }
            }

            $menu = Menu::where('id', $data['menu_id'])->first();

            $cartToppings = CartTopping::where('cart_id', $carts->id)->get();
            $toppingPrice = 0;
            if ($cartToppings != null) {
                foreach ($cartToppings as $topping) {
                    $toppingPrice += $topping->topping->price * $topping->quantity;
                }
            }

            $calculatePrice = $menu->price * $menu->quantity + $toppingPrice;
            $carts->price = $calculatePrice;
            $carts->save();

            $menu->stock = $menu->stock - $data['quantity'];
            $menu->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Cart created successfully',
            'data' => $carts,
        ], 201);
    }

    public function updateCart(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'nullable|integer',
            'toppings' => 'nullable|array',
            'toppings.*.topping_id' => 'required_with:toppings|integer|exists:toppings,id',
            'toppings.*.quantity' => 'required_with:toppings|integer',
            'menu_id' => 'nullable|integer|exists:menus,id',
            'variant_id' => 'nullable|integer|exists:variants,id',
        ]);

        $cart = Cart::find($id);

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found',
            ], 404);
        }

        if ($request->has('quantity')) {
            $cart->quantity = $request->quantity;
        }

        if ($request->has('menu_id')) {
            $menu = Menu::find($request->menu_id);
            $cart->menu_id = $request->menu_id;
            $cart->price = $menu->price * ($request->quantity ?? $cart->quantity);
            $menu->stock -= ($request->quantity ?? $cart->quantity);
            $menu->save();
        }

        if ($request->has('variant_id')) {
            $cart->variant_id = $request->variant_id;
        }

        if ($request->has('toppings')) {
            CartTopping::where('cart_id', $cart->id)->delete();

            $toppingPrice = 0;
            foreach ($request->toppings as $topping) {
                $cartTopping = CartTopping::create([
                    'cart_id' => $cart->id,
                    'topping_id' => $topping['topping_id'],
                    'quantity' => $topping['quantity'],
                ]);
                $toppingPrice += $cartTopping->topping->price * $topping['quantity'];
            }

            $menu = $menu ?? Menu::find($cart->menu_id);
            $cart->price = $menu->price * $cart->quantity + $toppingPrice;
        }

        $cart->save();

        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully',
            'data' => new CartResource($cart),
        ], 200);
    }

    

    public function getCart()
    {
        $cart = Cart::with(['menu', 'cartToppings.topping','variant'])->get();

        if ($cart->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => CartResource::collection($cart),
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
