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
            'status' => ['required', Rule::in(['selesai', 'sedang diproses', 'batal', 'pesanan siap', 'menunggu batal', 'menunggu pembayaran','menunggu pengembalian dana'])],
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
        if ($request->payment_method == 'tunai'){
            $adminTokens = Admin::whereNotNull('notification_token')->pluck('notification_token');
            foreach ($adminTokens as $adminToken) {
                $this->firebaseService->sendToAdmin($adminToken, 'Ada pesanan baru!', 'Pesanan dari ' . $order->user->username . ' telah diterima. Silahkan cek aplikasi Anda. Terima kasih! 🎉', '');
            }
            $this->firebaseService->sendNotification($user->notification_token, 'Pembayaran Berhasil', 'Pembayaran tunai untuk Order ID ' .$order->id. '. Telah terbayarkan', '');
        }
        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order,
        ], 201);
    }

    public function getChartOrder(Request $request)
    {
        $interval = $request->input('interval', 'daily');
        $now = Carbon::now();
        $startDate = null;
        $endDate = null;
        $dateFormat = null;
        $period = null;

        switch ($interval) {
            case 'weekly':
                $startDate = $now->copy()->startOfDay()->subDays(6); // Last 7 days including today
                $endDate = $now->copy()->endOfDay(); // End today
                $dateFormat = '%Y-%m-%d';
                $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate->addDay()); // Iterate up to today
                break;

            case 'monthly':
                $startDate = $now->copy()->startOfDay()->subDays(29); // Last 30 days including today
                $endDate = $now->copy()->endOfDay(); // End today
                $dateFormat = '%Y-%m-%d';
                $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate->addDay()); // Iterate over last 30 days
                break;

            case 'yearly':
                $startDate = $now->copy()->startOfMonth()->subMonths(11); // Last 12 months including current
                $endDate = $now->copy()->endOfMonth(); // End of the month
                $dateFormat = '%Y-%m';
                $period = new \DatePeriod($startDate, new \DateInterval('P1M'), $endDate->addMonth()); // Iterate over last 12 months
                break;

            default:
                $startDate = $now->copy()->startOfDay()->subDays(6); // Default to last 7 days (weekly)
                $endDate = $now->copy()->endOfDay(); // End today
                $dateFormat = '%Y-%m-%d';
                $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate->addDay()); // Iterate up to today
                break;
        }

        $orders = Order::where('status', 'selesai')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as date, COUNT(*) as total")
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $data = [];
        $overallTotal = 0;

        foreach ($period as $date) {
            $formattedDate = $date->format(str_replace('%', '', $dateFormat));
            if ($formattedDate > $now->format('Y-m-d')) {
                break; // Stop iteration if the date exceeds today
            }
            $total = isset($orders[$formattedDate]) ? (int)$orders[$formattedDate] : 0;
            $data[] = [
                'date' => $formattedDate,
                'total' => $total,
            ];
            $overallTotal += $total;
        }

        return response()->json([
            'data' => $data,
            'overall_total' => $overallTotal,
        ]);
    }


   public function getChartRevenue(Request $request)
    {
        $interval = $request->input('interval', 'daily');
        $now = Carbon::now();
        $startDate = null;
        $endDate = null;
        $dateFormat = null;
        $period = null;

        switch ($interval) {
            case 'weekly':
                $startDate = $now->copy()->startOfDay()->subDays(6); // Last 7 days including today
                $endDate = $now->copy()->endOfDay(); // End today
                $dateFormat = '%Y-%m-%d';
                $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate->addDay()); // Iterate up to today
                break;

            case 'monthly':
                $startDate = $now->copy()->startOfDay()->subDays(29); // Last 30 days including today
                $endDate = $now->copy()->endOfDay(); // End today
                $dateFormat = '%Y-%m-%d';
                $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate->addDay()); // Iterate over last 30 days
                break;

            case 'yearly':
                $startDate = $now->copy()->startOfMonth()->subMonths(11); // Last 12 months including current
                $endDate = $now->copy()->endOfMonth();
                $dateFormat = '%Y-%m';
                $period = new \DatePeriod($startDate, new \DateInterval('P1M'), $endDate->addMonth(0));
                break;

            default:
                $startDate = $now->copy()->startOfDay()->subDays(6); // Default to last 7 days (weekly)
                $endDate = $now->copy()->endOfDay(); // End today
                $dateFormat = '%Y-%m-%d';
                $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate->addDay()); // Iterate up to today
                break;
        }

        $revenues = Order::where('status', 'selesai')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as date, SUM(price_order) as total")
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $data = [];
        $overallTotal = 0;

        foreach ($period as $date) {
            $formattedDate = $date->format(str_replace('%', '', $dateFormat));
            if ($formattedDate > $now->format('Y-m-d')) {
                break; // Stop iteration if the date exceeds today
            }
            $total = isset($revenues[$formattedDate]) ? (int)$revenues[$formattedDate] : 0;
            $data[] = [
                'date' => $formattedDate,
                'total' => $total,
            ];
            $overallTotal += $total;
        }

        return response()->json([
            'data' => $data,
            'overall_total' => $overallTotal,
        ]);
    }

    public function cancelOrder(Request $request, $id)
    {
        $order = Order::where('id', $id)->first();

        if ($order->payment_method == 'tunai') {
            $validator = Validator::make($request->all(), [
                'reason_cancel' => 'required|string',
                'cancel_method' => ['required', Rule::in(['tunai', 'BCA', 'BNI', 'BRI', 'BSI', 'Mandiri'])],
            ]);
            $order->reason_cancel = $request->reason_cancel;
            $order->cancel_method = $request->cancel_method;
            $order->status = 'menunggu batal';
            $order->save();

            $adminTokens = Admin::whereNotNull('notification_token')->pluck('notification_token');
            foreach ($adminTokens as $adminToken) {
                $this->firebaseService->sendToAdmin($adminToken, 'Permintaan pembatalan order',  'Terdapat permintaan pembatalan order dari ' . $request->user()->name . '. Silahkan cek aplikasi Anda',
                '');
            }
             // Send notification to the user
            $this->firebaseService->sendNotification(
                $request->user()->notification_token,
                'Pesanan anda telah Dibatalkan',
                'Pembayaran untuk Order ' . $order->id . ' menunggu pembatalan',
                ''
            );
            return response()->json([
                'success' => true,
                'message' => 'Order canceled successfully',
                'data' => $order->load(['orderDetails.menu']),
            ], 200);
        }else{
            $validator = Validator::make($request->all(), [
            'reason_cancel' => 'required|string',
            'cancel_method' => ['required', Rule::in(['tunai', 'BCA', 'BNI', 'BRI', 'BSI', 'Mandiri'])],
            'no_rekening' => 'required|integer',
        ]);
         // Fetch the admin (assuming there's only one or a specific admin you want to notify)
         $adminTokens = Admin::whereNotNull('notification_token')->pluck('notification_token');
            foreach ($adminTokens as $adminToken) {
                $this->firebaseService->sendToAdmin(
                    $adminToken, // $notification_token
                    'Permintaan pembatalan order',
                    'Terdapat permintaan pembatalan order dari ' . $request->user()->name . '. Silahkan cek aplikasi Anda',
                    ''
                 );
            }

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        // Send notification to the user
        $this->firebaseService->sendNotification(
            $request->user()->notification_token,
            'Pesanan anda telah Dibatalkan',
            'Pembayaran untuk Order ' . $order->id . ' menunggu pembatalan',
            ''
        );
        // Set the order status to "menunggu batal" and apply the cancelation details
        $order->status = 'menunggu batal';
        $order->reason_cancel = $request->reason_cancel;
        $order->cancel_method = $request->cancel_method;
        $order->no_rekening = $request->no_rekening;
        // Apply the admin fee
        $order->admin_fee = 0;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order canceled successfully ',
            'data' => $order->load(['orderDetails.menu']),
            'admin_fee' => $order->admin_fee
        ], 200);
        }        
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
            'status' => ['required', Rule::in(['selesai', 'sedang diproses', 'batal', 'pesanan siap', 'menunggu batal','menunggu pengembalian dana'])],
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

            $completedOrdersCount = Order::where('user_id', $order->user_id)
            ->where('status', 'selesai')
            ->count();

        // Verify user if they have completed 14 orders
        if ($completedOrdersCount >= 14 && !$order->user->user_verified) {
            $order->user->user_verified = true;
            $order->user->save();
            $this->firebaseService->sendNotification($userNotificationToken, 'Selamat! Anda telah diverifikasi', 'Anda telah menyelesaikan 15 pesanan dan telah diverifikasi', '');
        }

        } elseif ($request->status == 'pesanan siap') {
            $this->firebaseService->sendNotification($userNotificationToken, 'Pesanan anda telah Siap', 'Silahkan ambil makanan anda dengan Order ID ' . $order->id . ' di kedai Warmindo', '');
        } elseif ($request->status == 'menunggu batal') {
            $this->firebaseService->sendNotification($userNotificationToken, 'Permintaan pembatalan order', 'Permintaan pembatalan order anda dengan Order ID ' . $order->id . ' sedang diproses', '');
        } elseif ($request->status == 'sedang diproses') {
            $this->firebaseService->sendNotification($userNotificationToken, 'Pesanan anda sedang diproses', 'Pesanan anda dengan Order ID ' . $order->id . ' sedang diproses', '');
        } elseif ($request->status == 'menunggu pengembalian dana') {
            $this->firebaseService->sendNotification($userNotificationToken, 'Permintaan pengembalian dana', 'Permintaan pengembalian dana anda dengan Order ID ' . $order->id . ' sedang diproses', '');
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
