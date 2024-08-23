<?php

namespace App\Http\Controllers;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\History;
use Carbon\Carbon;

class OrderController extends Controller
{
    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

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
    
        $dailyChart = [];
        $weeklyChart = [];
        $monthlyChart = [];
        $yearlyChart = [];
    
        $totalDailyOrders = 0;
        $totalWeeklyOrders = 0;
        $totalMonthlyOrders = 0;
        $totalYearlyOrders = 0;
    
        foreach ($orders as $order) {
            $date = Carbon::parse($order->created_at);
            
            // Daily orders count
            $dailyKey = $date->format('d-m-Y');
            if (array_key_exists($dailyKey, $dailyChart)) {
                $dailyChart[$dailyKey] += 1;
            } else {
                $dailyChart[$dailyKey] = 1;
            }
            $totalDailyOrders += 1;
    
            // Weekly orders count (group by week number of the year)
            $weeklyKey = $date->format('o-W'); // 'o' is for ISO-8601 year number, 'W' is for ISO-8601 week number
            if (array_key_exists($weeklyKey, $weeklyChart)) {
                $weeklyChart[$weeklyKey] += 1;
            } else {
                $weeklyChart[$weeklyKey] = 1;
            }
            $totalWeeklyOrders += 1;
    
            // Monthly orders count
            $monthlyKey = $date->format('Y-m'); // 'Y' for year, 'm' for month
            if (array_key_exists($monthlyKey, $monthlyChart)) {
                $monthlyChart[$monthlyKey] += 1;
            } else {
                $monthlyChart[$monthlyKey] = 1;
            }
            $totalMonthlyOrders += 1;
    
            // Yearly orders count
            $yearlyKey = $date->format('Y'); // 'Y' for year
            if (array_key_exists($yearlyKey, $yearlyChart)) {
                $yearlyChart[$yearlyKey] += 1;
            } else {
                $yearlyChart[$yearlyKey] = 1;
            }
            $totalYearlyOrders += 1;
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Chart retrieved successfully',
            'data' => [
                'daily_chart' => $dailyChart,
                'weekly_chart' => $weeklyChart,
                'monthly_chart' => $monthlyChart,
                'yearly_chart' => $yearlyChart,
                'total_orders' => [
                    'daily' => $totalDailyOrders,
                    'weekly' => $totalWeeklyOrders,
                    'monthly' => $totalMonthlyOrders,
                    'yearly' => $totalYearlyOrders,
                ],
            ],
        ], 200);
    }
    

public function getRevenueBreakdown()
{
    $orders = Order::where('status', 'selesai')->get();

    $dailyRevenue = [];
    $weeklyRevenue = [];
    $monthlyRevenue = [];
    $yearlyRevenue = [];
    $totalRevenue = 0;

    foreach ($orders as $order) {
        $date = Carbon::parse($order->created_at);
        $day = $date->format('d-m-Y');
        $week = $date->format('W-Y'); // Week number and Year
        $month = $date->format('m-Y');
        $year = $date->format('Y');

        $totalRevenue += $order->price_order;

        // Daily Revenue
        if (array_key_exists($day, $dailyRevenue)) {
            $dailyRevenue[$day] += $order->price_order;
        } else {
            $dailyRevenue[$day] = $order->price_order;
        }

        // Weekly Revenue
        if (array_key_exists($week, $weeklyRevenue)) {
            $weeklyRevenue[$week] += $order->price_order;
        } else {
            $weeklyRevenue[$week] = $order->price_order;
        }

        // Monthly Revenue
        if (array_key_exists($month, $monthlyRevenue)) {
            $monthlyRevenue[$month] += $order->price_order;
        } else {
            $monthlyRevenue[$month] = $order->price_order;
        }

        // Yearly Revenue
        if (array_key_exists($year, $yearlyRevenue)) {
            $yearlyRevenue[$year] += $order->price_order;
        } else {
            $yearlyRevenue[$year] = $order->price_order;
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'Revenue breakdown retrieved successfully',
        'data' => [
            'daily' => $dailyRevenue,
            'weekly' => $weeklyRevenue,
            'monthly' => $monthlyRevenue,
            'yearly' => $yearlyRevenue,
            'totalRevenue' => $totalRevenue,
        ],
    ], 200);
}


    public function cancelOrder(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'reason_cancel' => 'required|string',
        'cancel_method' => ['required', Rule::in(['tunai', 'BCA', 'BNI', 'BRI', 'BSI', 'Mandiri'])],
        'no_rekening' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $order = Order::where('id', $id)->first();

    if (!$order) {
        return response()->json(['message' => 'Order not found'], 404);
    }

    // Send notification to the user
    $this->firebaseService->sendNotification(
        $request->user()->notification_token,
        'Pesanan anda telah Dibatalkan',
        'Pembayaran untuk Order ' . $order->id . ' telah terbatalkan',
        ''
    );

    // Fetch the admin (assuming there's only one or a specific admin you want to notify)
    $admin = Admin::first(); // You can modify this line to get a specific admin if needed

    if ($admin) {
        // Send notification to the admin
        $this->firebaseService->sendToAdmin(
            $admin->notification_token,
            'Permintaan pembatalan order',
            'Terdapat permintaan pembatalan order dari ' . $request->user()->name . '. Silahkan cek aplikasi Anda',
            ''
        );
    }

    // Set the order status to "menunggu batal" and apply the cancelation details
    $order->status = 'menunggu batal';
    $order->reason_cancel = $request->reason_cancel;
    $order->cancel_method = $request->cancel_method;
    $order->no_rekening = $request->no_rekening;

    // Apply the admin fee
    $order->admin_fee = 6500;

    $order->save();

    return response()->json([
        'success' => true,
        'message' => 'Order canceled successfully with an admin fee',
        'data' => $order->load(['orderDetails.menu']),
        'admin_fee' => $order->admin_fee
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

        // Fetch the order from the database
        $order = Order::with('user')->where('id', $id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Fetch the user's notification token from the order
        $userNotificationToken = $order->user->notification_token;

        // Update the status and send notifications based on the status
        if ($request->status == 'batal') {
            $this->firebaseService->sendNotification($userNotificationToken, 'Pesanan anda telah Dibatalkan', 'Pembayaran untuk Order ID ' . $order->id . ' telah terbatalkan', '');
        } elseif ($request->status == 'selesai') {
            $this->firebaseService->sendNotification($userNotificationToken, 'Pesanan anda telah Selesai', 'Makanan anda dengan Order ID ' . $order->id . ' telah selesai dan sampai di tangan anda', '');
        } elseif ($request->status == 'pesanan siap') {
            $this->firebaseService->sendNotification($userNotificationToken, 'Pesanan anda telah Siap', 'Silahkan ambil makanan anda dengan Order ID ' . $order->id . ' di kedai Warmindo', '');
        } elseif ($request->status == 'menunggu batal') {
            $this->firebaseService->sendNotification($userNotificationToken, 'Permintaan pembatalan order', 'Permintaan pembatalan order anda dengan Order ID ' . $order->id . ' sedang diproses', '');
        } elseif ($request->status == 'sedang diproses') {
            $this->firebaseService->sendNotification($userNotificationToken, 'Pesanan anda sedang diproses', 'Pesanan anda dengan Order ID ' . $order->id . ' sedang diproses', '');
        }

        // Update the order status in the database
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
