<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderDetailResource;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderDetailTopping;
use App\Models\Topping;
use Illuminate\Http\Request;

class OrderDetailController extends Controller
{
    public function createOrderDetail(Request $request)
    {
        $request->validate([
            'menu_id' => 'required|integer|exists:menus,id',
            'quantity' => 'required|integer',
            'order_id' => 'required|integer|exists:orders,id',
            'notes' => 'nullable|string',
            'toppings' => 'nullable|array',

        ]);
        $orderDetail = OrderDetail::create([
            'quantity' => $request->quantity,
            'menu_id' => $request->menu_id,
            'order_id' => $request->order_id,
            'notes' => $request->notes,
        ]);
        $toppingPrice = 0;
        if ($request->toppings != null) {
            foreach ($request->toppings as $topping) {
                OrderDetailTopping::create([
                    'order_detail_id' => $orderDetail->id,
                    'topping_id' => $topping['topping_id'],
                    'quantity' => $topping['quantity'],
                ]);
            }
        }

        $menu = Menu::where('id', $request->menu_id)->first();
        $topping = Topping::where ('id', $request ->topping_id)->get();

        $orderTopping = OrderDetailTopping::where('order_detail_id', $orderDetail->id)->get();

        if($orderTopping != null) {
            foreach ($orderTopping as $topping) {
                $toppingPrice += $topping->topping->price * $topping->quantity;
            }
        }
        $calculatePrice = $menu->price + $toppingPrice;
        $orderDetail->price = $calculatePrice;
        $orderDetail->save();

        $menu->stock = $menu->stock - $request->quantity;
        $menu->save();

        $this->updatePrice($request->order_id);


        return response()->json([
            'status' => 'success',
            'message' => 'Order detail created successfully',
            'data' => $orderDetail
        ], 201);

    }

    public function updatePrice($id) 
    {

        $order = Order::where('id', $id)->first();
        $orderDetails = OrderDetail::where('order_id', $order->id)->get();
        $totalPrice = $orderDetails->sum('price');

        $order->price_order = $totalPrice;
        $order->save();
    }

    public function getOrderDetails(Request $request) 
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id'
        ]);
        $orderDetails = OrderDetail::where('order_id', $request->order_id)->get();
        // dd($orderDetails->toArray()->menu);
        return response()->json([
            'status' => 'success',
            'message' => 'List of order details',
            'data' => OrderDetailResource::collection($orderDetails)
        ], 200);
    }
}
