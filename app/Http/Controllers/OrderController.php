<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['orderDetails.menu', 'history.user'])->get();

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

        $totalPrice = 0;
        foreach ($request->menus as $menu) {
            $totalPrice += $menu['quantity'] * $menu['price'];
        }

        $order = Order::create([
            'user_id' => $request->user_id,
            'price_order' => $totalPrice,
            'order_date' => $request->order_date,
            'status' => $request->status,
            'payment' => $request->payment,
            'refund' => $request->refund,
            'note' => $request->note,
        ]);

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
            'data' => $order->load(['orderDetails.menu', 'history.user']),
        ], 201);
    }

    public function getSalesStatistics()
    {
        $weeklySales = Order::where('status', 'selesai')
            ->where('order_date', '>=', Carbon::now()->subWeek())
            ->count();

        $monthlySales = Order::where('status', 'selesai')
            ->where('order_date', '>=', Carbon::now()->subMonth())
            ->count();

        $yearlySales = Order::where('status', 'selesai')
            ->where('order_date', '>=', Carbon::now()->subYear())
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Sales statistics retrieved successfully',
            'data' => [
                'weekly_sales' => $weeklySales,
                'monthly_sales' => $monthlySales,
                'yearly_sales' => $yearlySales,
            ],
        ], 200);
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
            'data' => $order->load(['orderDetails.menu', 'history.user']),
        ], 200);
    }
}
