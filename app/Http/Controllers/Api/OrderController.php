<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'     => 'required|exists:users,id',
            'menuID'     => 'required|exists:menus,menuID',
            'price_order' => 'required|numeric',
            'order_date'  => 'required|date',
            'status'      => 'required|string|max:255',
            'payment'     => 'required|numeric',
            'refund'      => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $order = Order::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order
        ], 201);
    }

    public function index()
    {
        $orders = Order::all();

        return response()->json([
            'success' => true,
            'message' => 'List of orders',
            'data' => $orders
        ], 200);
    }

    public function show($id)
    {
        $order = Order::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Order details',
            'data' => $order
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'user_id'     => 'required|exists:users,id',
            'menuID'     => 'required|exists:menus,menuID',
            'price_order' => 'required|numeric',
            'order_date'  => 'required|date',
            'status'      => 'required|string|max:255',
            'payment'     => 'required|numeric',
            'refund'      => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $order = Order::findOrFail($id);
        $order->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $order
        ], 200);
    }
}
