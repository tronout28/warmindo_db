<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\History;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $orders = Order::where('user_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'message' => 'List of orders',
            'data' => $orders
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['selesai', 'sedang diproses', 'batal', 'pesanan siap', 'menunggu batal', 'menunggu pembayaran'])],
            'note' => 'nullable|string',
            'payment_method' => ['nullable', Rule::in(['tunai','ovo', 'gopay', 'dana', 'linkaja', 'shopeepay', 'gopay', 'transfer'])],
            'order_method' => ['nullable', Rule::in(['dine-in', 'take-away', 'delivery'])],
        ]);

        $user = auth()->user();

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->refund && is_null($request->note)) {
            return response()->json(['note' => 'Note is required when refund is true'], 422);
        }

        $order = Order::create([
            'user_id' => $user->id,
            'status' => $request->status,
            'payment_method' => $request->payment_method,
            'order_method' => $request->order_method,
            'note' => $request->note,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order,
        ], 201);
    }

    public function getChart()
    {
        $orders = Order::where('status', 'selesai')->get();
        $chart = [];
        foreach ($orders as $order) {
            $date = Carbon::parse($order->created_at)->format('d-m-Y');
            if (array_key_exists($date, $chart)) {
                $chart[$date] += $order->price_order;
            } else {
                $chart[$date] = $order->price_order;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Chart retrieved successfully',
            'data' => $chart,
        ], 200);
    }

    public function updatepaymentmethod(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => ['required', Rule::in(['non-tunai', 'tunai'])],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $order = Order::where('id', $id)->first();
        $order->payment_method = $request->payment_method;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment method updated successfully',
            'data' => $order,
        ], 200);
    }

    public function updateNote (Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'note' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $order = Order::where('id', $id)->first();
        $order->note = $request->note;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Note updated successfully',
            'data' => $order,
        ], 200);
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['selesai', 'sedang diproses', 'batal', 'pesanan siap', 'menunggu batal'])],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->refund && is_null($request->note)) {
            return response()->json(['note' => 'Note is required when refund is true'], 422);
        }
        $order = Order::where('id', $id)->first();
        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $order->load(['orderDetails.menu', 'history.user']),
        ], 200);
    }


    
    public function show($id)
    {
        $order = Order::with(['orderDetails.menu', 'history.user'])->find($id);

        if (is_null($order)) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order details retrieved successfully',
            'data' => $order,
        ], 200);
    }

    public function tohistory($id)
    {
        $order = Order::where('id', $id)->first();
        $orderDetails = OrderDetail::where('order_id', $order->id)->get();

        $history = History::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            
        ]);

        foreach ($orderDetails as $orderDetail) {
            $orderDetail->history_id = $history->id;
            $orderDetail->save();
        }

        // $order->status = 'selesai';
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order moved to history successfully',
            'data' => $order->load(['orderDetails.menu', 'history.user']),
        ], 200);
    }

    public function filterbystatues (Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'status' => ['required', Rule::in(['selesai', 'sedang diproses', 'batal', 'pesanan siap', 'menunggu batal'])],
        ]);

        $orders = Order::where('status', $request->status)->where('user_id', $user->id)->get();
        if($orders == null) {
            return response([
                'status' => false,
                'message' => 'Order not found'
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => 'List of orders filtered by status',
            'data' => $orders,
        ], 200);
    }

    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully',
        ], 200);
    }
}
