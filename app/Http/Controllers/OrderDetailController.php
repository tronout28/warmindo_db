<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderDetailResource;
use App\Models\Menu;
use App\Models\OrderDetail;
use App\Models\OrderDetailTopping;
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

        $menu = Menu::where('id', $request->menu_id)->first();
        $calculatePrice = $menu->price * $request->quantity;
        $orderDetail->price = $calculatePrice;
        $orderDetail->save();

        $menu->stock = $menu->stock - $request->quantity;
        $menu->save();

        if ($request->toppings != null) {
            foreach ($request->toppings as $topping) {
                OrderDetailTopping::create([
                    'order_detail_id' => $orderDetail->id,
                    'topping_id' => $topping['topping_id'],
                    'quantity' => $topping['quantity'],
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order detail created successfully',
            'data' => $orderDetail
        ], 201);

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
