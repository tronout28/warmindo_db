<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('orderDetails.menu')->get();

        return response()->json([
            'success' => true,
            'message' => 'List of orders',
            'data' => $orders
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'menus' => 'required|array',
            'menus.*.menuID' => 'required|exists:menus,menuID',
            'menus.*.quantity' => 'required|integer|min:1',
            'menus.*.price' => 'required|numeric',
            'price_order' => 'required|numeric',
            'order_date' => 'required|date',
            'status' => ['required', Rule::in(['selesai', 'sedang diproses', 'batal', 'pesanan siap', 'menunggu batal'])],
            'payment' => 'required|numeric',
            'refund' => 'required|boolean',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->refund && is_null($request->note)) {
            return response()->json(['note' => 'Note is required when refund is true'], 422);
        }

        $order = Order::create($request->only('user_id', 'price_order', 'order_date', 'status', 'payment', 'refund', 'note'));

        foreach ($request->menus as $menu) {
            OrderDetail::create([
                'order_id' => $order->id,
                'menuID' => $menu['menuID'],
                'quantity' => $menu['quantity'],
                'price' => $menu['price'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order->load('orderDetails.menu'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'menus' => 'required|array',
            'menus.*.menuID' => 'required|exists:menus,menuID',
            'menus.*.quantity' => 'required|integer|min:1',
            'menus.*.price' => 'required|numeric',
            'refund' => 'required|boolean',
            'note' => 'nullable|string',
            'status' => ['required', Rule::in(['selesai', 'sedang diproses', 'batal', 'pesanan siap', 'menunggu batal'])],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->refund && is_null($request->note)) {
            return response()->json(['note' => 'Note is required when refund is true'], 422);
        }

        $order = Order::findOrFail($id);
        $order->update($request->only('user_id', 'price_order', 'order_date', 'status', 'payment', 'refund', 'note'));

        $order->orderDetails()->delete();
        foreach ($request->menus as $menu) {
            OrderDetail::create([
                'order_id' => $order->id,
                'menuID' => $menu['menuID'],
                'quantity' => $menu['quantity'],
                'price' => $menu['price'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $order->load('orderDetails.menu'),
        ], 200);
    }
}
