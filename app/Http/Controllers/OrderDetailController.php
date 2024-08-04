<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderDetailResource;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderDetailTopping;
use Illuminate\Http\Request;

class OrderDetailController extends Controller
{
    public function createOrderDetail(Request $request)
    {
        $request->validate([
            'datas' => 'required|array',
            'datas.*.quantity' => 'required|integer',
            'datas.*.toppings' => 'nullable|array',
            'datas.*.toppings.*.topping_id' => 'required|integer|exists:toppings,id',
            'datas.*.toppings.*.quantity' => 'required|integer',
            'datas.*.menu_id' => 'required|integer|exists:menus,id',
            'datas.*.variant_id' => 'nullable|integer|exists:variants,id',
            'datas.*.order_id' => 'required|integer|exists:orders,id',
            'datas.*.notes' => 'nullable|string',
        ]);

        foreach ($request->datas as $data) {
            $orderDetail = OrderDetail::create([
                'quantity' => $data['quantity'],
                'menu_id' => $data['menu_id'],
                'variant_id' => $data['variant_id']?? null,
                'order_id' => $data['order_id'],
                'notes' => $data['notes'],
            ]);

            if ($data['toppings'] != null) {
                foreach ($data['toppings'] as $topping) {
                    OrderDetailTopping::create([
                        'order_detail_id' => $orderDetail->id,
                        'topping_id' => $topping['topping_id'],
                        'quantity' => $topping['quantity'],
                    ]);
                }
            }

            $menu = Menu::where('id', $data['menu_id'])->first();
            $orderTopping = OrderDetailTopping::where('order_detail_id', $orderDetail->id)->get();
            $toppingPrice = 0;

            if ($orderTopping != null) {
                foreach ($orderTopping as $topping) {
                    $toppingPrice += $topping->topping->price * $topping->quantity;
                }
            }

            $calculatePrice = $menu->price * $data['quantity'] + $toppingPrice;
            $orderDetail->price = $calculatePrice;
            $orderDetail->save();

            $menu->stock -= $data['quantity'];
            $menu->save();

            $this->updatePrice($data['order_id']);
        }

        $orderDetail = OrderDetail::where('order_id', $request->datas[0]['order_id'])->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Order detail created successfully',
            'data' => $orderDetail
        ], 201);
    }

    public function updatePrice($id)
    {
        $order = Order::find($id);
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

        return response()->json([
            'status' => 'success',
            'message' => 'List of order details',
            'data' => OrderDetailResource::collection($orderDetails)
        ], 200);
    }
}
