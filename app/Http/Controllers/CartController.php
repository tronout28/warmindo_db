<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use App\Http\Resources\CartResource;
use App\Models\carttopping;
use App\Models\Menu;

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

    public function createOrderDetail(Request $request)
    {
        $request->validate([
            'datas' => 'required|array',
            'datas.*.quantity' => 'required|integer',
            'datas.*.toppings' => 'nullable|array',
            'datas.*.toppings.*.topping_id' => 'required|integer|exists:toppings,id',
            'datas.*.toppings.*.quantity' => 'required|integer',
            'datas.*.menu_id' => 'required|integer|exists:menus,id',
            'datas.*.notes' => 'nullable|string',
        ]);
        foreach ($request->datas as $data) {

            $carts = Cart::create([
                'quantity' => $data['quantity'],
                'menu_id' => $data['menu_id'],
                'notes' => $data['notes'],
            ]);            
            if ($data['toppings'] != null) {
                foreach ($data['toppings'] as $topping) {
                    carttopping::create([
                        'cart_id' => $carts->id,
                        'topping_id' => $topping['topping_id'],
                        'quantity' => $topping['quantity'],
                    ]);
                }
            }
            $menu = Menu::where('id', $data['menu_id'])->first();

            $orderTopping = carttopping::where('cart_id', $carts->id)->get();
            $toppingPrice = 0;
            if($orderTopping != null) {
                foreach ($orderTopping as $topping) {
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
            'message' => 'Order detail created successfully',
            'data' => $carts
        ], 201);

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
