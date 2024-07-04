<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'menuID' => 'required|exists:menus,menuID',
            'price_order' => 'required|numeric',
            'order_date' => 'required|date',
            'status' => ['required', Rule::in(['done', 'in progress', 'cancelled', 'ready', 'waiting to cancelled'])],
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

        $order = Order::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'menuID' => 'required|exists:menus,menuID',
            'refund' => 'required|boolean',
            'note' => 'nullable|string',
            'status' => ['required', Rule::in(['done', 'in progress', 'cancelled', 'ready', 'waiting to cancelled'])],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->refund && is_null($request->note)) {
            return response()->json(['note' => 'Note is required when refund is true'], 422);
        }

        $order = Order::findOrFail($id);
        $order->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $order,
        ], 200);
    }
}
