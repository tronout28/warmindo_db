<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\Order;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:users,id',
            'description' => 'required|string',
        ]);

        $history = History::create([
            'order_id' => $request->order_id,
            'user_id' => $request->user_id,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'History created successfully',
            'data' => $history,
        ], 201);
    }

    public function show($id)
    {
        $history = History::with('order', 'user')->find($id);

        if (!$history) {
            return response()->json([
                'success' => false,
                'message' => 'History not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'History retrieved successfully',
            'data' => $history,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'description' => 'required|string',
        ]);

        $history = History::find($id);

        if (!$history) {
            return response()->json([
                'success' => false,
                'message' => 'History not found',
            ], 404);
        }

        $history->update([
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'History updated successfully',
            'data' => $history,
        ], 200);
    }

    public function destroy($id)
    {
        $history = History::find($id);

        if (!$history) {
            return response()->json([
                'success' => false,
                'message' => 'History not found',
            ], 404);
        }

        $history->delete();

        return response()->json([
            'success' => true,
            'message' => 'History deleted successfully',
        ], 200);
    }

    public function toHistory(Request $request) 
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        $order = Order::where('id', $request->order_id)->first();
        $history = History::create([
            'user_id' => $order->user_id,
            'price_order' => $order->price_order,
            'status' => $order->status,
            'note' => $order->note,
        ]);

        return response([
            'status' => 'success',
            'message' => 'History created successfully',
            'data' => $history,
        ]);
    }

    public function orderToHistory() 
    {
        $orders = Order::all();
        foreach ($orders as $order) {
            $history = History::create([
                'user_id' => $order->user_id,
                'price_order' => $order->price_order,
                'status' => $order->status,
                'note' => $order->note,
            ]);
        }

        return response([
            'status' => 'success',
            'message' => 'History created successfully',
            'data' => $history,
        ]);
    }
}
