<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use App\Http\Resources\CartResource;
use App\Models\CartTopping;
use App\Models\Menu;
use Illuminate\Support\Facades\Auth;


class CartController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
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
            'datas.*.toppings.*.topping_id' => 'nullable|integer|exists:toppings,id',
            'datas.*.toppings.*.quantity' => 'nullable|integer',
            'datas.*.menu_id' => 'required|integer|exists:menus,id',
            'datas.*.variant_id' => 'nullable|integer|exists:variants,id',
        ]);

        $user = Auth::user();

        foreach ($request->datas as $data) {
            $menu = Menu::where('id', $data['menu_id'])->first();
            if (!$menu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu item not found',
                ], 404);
            }

            $cart = Cart::create([
                'quantity' => $data['quantity'],
                'menu_id' => $data['menu_id'],
                'variant_id' => $data['variant_id'] ?? null,
                'user_id' => $user->id,
            ]);

            $toppingPrice = 0;
            if (isset($data['toppings'])) {
                foreach ($data['toppings'] as $topping) {
                    // Check for null values before creating CartTopping
                    if (!is_null($topping['topping_id']) && !is_null($topping['quantity'])) {
                        $cartTopping = CartTopping::create([
                            'cart_id' => $cart->id,
                            'topping_id' => $topping['topping_id'],
                            'quantity' => $topping['quantity'],
                        ]);
                        $toppingPrice += $cartTopping->topping->price * $topping['quantity'];
                    }
                }
            }

            // Calculate total price: menu price * quantity + total topping price
            $calculatePrice = ($menu->price * $data['quantity']) + $toppingPrice;
            $cart->price = $calculatePrice;
            $cart->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Cart created successfully',
            'data' => $cart,
        ], 201);
    }


    public function updateCart(Request $request, $id)
    {
        $request->validate([
            'datas' => 'required|array',
            'datas.*.quantity' => 'nullable|integer',
            'datas.*.toppings' => 'nullable|array',
            'datas.*.toppings.*.topping_id' => 'nullable|integer|exists:toppings,id',
            'datas.*.toppings.*.quantity' => 'nullable|integer',
            'datas.*.menu_id' => 'nullable|integer|exists:menus,id',
            'datas.*.variant_id' => 'nullable|integer|exists:variants,id',
        ]);
    
        // Find the cart by ID
        $cart = Cart::find($id);
    
        // If cart not found, return error response
        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found',
            ], 404);
        }
    
        // Loop through each data set in the datas array
        foreach ($request->datas as $data) {
            // Update cart quantity if provided
            if (isset($data['quantity'])) {
                $cart->quantity = $data['quantity'];
            }
    
            // Update cart menu_id and recalculate price if menu_id provided
            if (isset($data['menu_id'])) {
                $menu = Menu::find($data['menu_id']);
                $cart->menu_id = $data['menu_id'];
                $cart->price = $menu->price * ($data['quantity'] ?? $cart->quantity);
                $menu->stock -= ($data['quantity'] ?? $cart->quantity);
                $menu->save();
            }
    
            // Update cart variant_id if provided
            if (isset($data['variant_id'])) {
                $cart->variant_id = $data['variant_id'];
            }
    
            // Handle toppings if provided
            if (isset($data['toppings'])) {
                // Delete existing cart toppings
                CartTopping::where('cart_id', $cart->id)->delete();
    
                $toppingPrice = 0;
                // Add new cart toppings
                foreach ($data['toppings'] as $topping) {
                    $cartTopping = CartTopping::create([
                        'cart_id' => $cart->id,
                        'topping_id' => $topping['topping_id'],
                        'quantity' => $topping['quantity'],
                    ]);
                    $toppingPrice += $cartTopping->topping->price * $topping['quantity'];
                }
    
                // Recalculate total price with toppings
                $menu = $menu ?? Menu::find($cart->menu_id);
                $cart->price = $menu->price * $cart->quantity + $toppingPrice;
            }
    
            // Save the cart changes
            $cart->save();
        }
    
        // Return success response with updated cart data
        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully',
            'data' => new CartResource($cart),
        ], 200);
    }
    

    

    public function getCart()
    {
        $user = Auth::user();

        $cart = Cart::with(['menu', 'cartToppings.topping', 'variant'])
            ->where('user_id', $user->id)
            ->get();

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
