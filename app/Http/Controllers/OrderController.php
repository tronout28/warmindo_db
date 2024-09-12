<?php

namespace App\Http\Controllers;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\AlamatUser;
use App\Models\Transaction;
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
            'status' => ['required', Rule::in(['selesai', 'sedang diproses', 'batal', 'pesanan siap', 'menunggu pembayaran','menunggu pengembalian dana','konfirmasi pesanan'])],
            'note' => 'nullable|string',
            'payment_method' => ['nullable', Rule::in(['tunai','ovo', 'gopay', 'dana', 'linkaja', 'shopeepay', 'transfer'])],
            'order_method' => ['nullable', Rule::in(['dine-in', 'take-away', 'delivery'])],
            'alamat_users_id' => 'required_if:order_method,delivery|exists:alamat_users,id', // Jika delivery, alamat harus ada
        ]);


        $user = auth()->user();

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Jika order_method adalah delivery, hitung jarak antara admin dan alamat user
        if ($request->order_method == 'delivery') {
            // Ambil alamat user berdasarkan ID yang diberikan
            $alamatUser = AlamatUser::findOrFail($request->alamat_users_id);
            // Ambil posisi admin pertama (anggap admin pertama sebagai acuan)

            // Hitung jarak antara admin dan user menggunakan metode Haversine
            $distance = $this->calculateDistance(-6.7525374, 110.842826, $alamatUser->latitude, $alamatUser->longitude);

            // Periksa apakah jarak lebih dari 12km
            if ($distance >= 12) {
                return response()->json(['error' => 'Jarak melebihi 12km, tidak dapat menggunakan metode delivery'], 422);
            }
            $driverFee = $this->calculateDeliveryFee($distance);

            // Isi driver_fee ke request untuk disimpan nanti
            $request->merge(['driver_fee' => $driverFee]);
        }

        // Buat order
        $order = Order::create([
            'user_id' => $user->id,
            'status' => $request->status,
            'payment_method' => $request->payment_method,
            'order_method' => $request->order_method,
            'note' => $request->note,
            'alamat_users_id' => $request->alamat_users_id ?? null,
            'driver_fee' => $request->driver_fee ?? null, // Hanya terisi jika delivery
        ]);

        if ($request->payment_method == 'tunai') {
            // Kirim notifikasi jika pembayaran tunai
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

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radius bumi dalam kilometer

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c; // Jarak dalam kilometer

        return $distance;
    }
    
    private function calculateDeliveryFee($distance)
    {
        // Convert distance to meters
        $distanceInMeters = $distance * 1000;

        // Initialize delivery fee
        $deliveryFee = 0;

        // Check if distance is within 1.5 km
        if ($distanceInMeters <= 1500) {
            $deliveryFee = 3000; // Flat rate for up to 1.5 km
        } else {
            // Calculate additional fee for distances over 1.5 km
            $extraDistanceInMeters = $distanceInMeters - 1500;

            // Calculate the number of 500-meter segments in the extra distance
            $extraSegments = ceil($extraDistanceInMeters / 500);

            // Add 1000 for each 500-meter segment
            $deliveryFee = 3000 + ($extraSegments * 1000);
        }

        return $deliveryFee;
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
        $transaction = Transaction::where('order_id', $order->id)->first(); // Ambil transaction terkait dengan order


        if ($order->payment_method == 'tunai') {
            $validator = Validator::make($request->all(), [
                'reason_cancel' => 'required|string',
                'cancel_method' => ['required', Rule::in(['tunai', 'BCA', 'BNI', 'BRI', 'BSI', 'Mandiri'])],
            ]);
            
            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $order->reason_cancel = $request->reason_cancel;
            $order->cancel_method = $request->cancel_method;
            $order->status = 'batal';
            
            if($order->status != 'sedang diproses'){
                $order->save();
                $adminTokens = Admin::whereNotNull('notification_token')->pluck('notification_token');
                foreach ($adminTokens as $adminToken) {
                    $this->firebaseService->sendToAdmin($adminToken, 'Permintaan pembatalan order',  'Terdapat permintaan pembatalan order dari ' . $request->user()->name . '. Silahkan cek aplikasi Anda', '');
                }

                $this->firebaseService->sendNotification($request->user()->notification_token, 'Pesanan anda telah Dibatalkan', 'Pembayaran untuk Order ' . $order->id . ' menunggu pembatalan', '');

                return response()->json([
                    'success' => true,
                    'message' => 'Order canceled successfully',
                    'data' => $order->load(['orderDetails.menu']),
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be canceled',
                ], 400);
            }
        } else {
            $validator = Validator::make($request->all(), [
                'reason_cancel' => 'required|string',
                'cancel_method' => ['required', Rule::in(['tunai', 'BCA', 'BNI', 'BRI', 'BSI', 'Mandiri'])],
                'no_rekening' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Set persentase fee berdasarkan payment_channel
            $paymentChannel = $transaction->payment_channel; // Pastikan payment_channel dalam huruf kecil
            $feePercent = 0;

            switch ($paymentChannel) {
                case 'OVO':
                case 'DANA':
                case 'LINKAJA':
                    $feePercent = 1.5;
                    break;
                case 'SHOPEEPAY':
                    $feePercent = 1.8;
                    break;
                case 'JENIUSPAY':
                    $feePercent = 2.0;
                    break;
                case 'QRIS':
                    $feePercent = 0.63;
                    break;
                default:
                    $feePercent = 0; // Tidak ada potongan jika payment_channel tidak ditemukan
                    break;
            }

            // Calculate admin fee and adjust price_order
            $adminFeeAmount = $order->price_order * ($feePercent / 100);
            $order->price_order = $order->price_order - $adminFeeAmount; // Subtract the admin fee from price_order
            $order->admin_fee = $adminFeeAmount; // Store the admin fee

            // Update status dan informasi pembatalan
            $order->status = 'menunggu pengembalian dana';
            $order->reason_cancel = $request->reason_cancel;
            $order->cancel_method = $request->cancel_method;
            $order->no_rekening = $request->no_rekening;

            $order->save();

            // Kirim notifikasi ke admin
            $adminTokens = Admin::whereNotNull('notification_token')->pluck('notification_token');
            foreach ($adminTokens as $adminToken) {
                $this->firebaseService->sendToAdmin(
                    $adminToken,
                    'Permintaan pembatalan order',
                    'Terdapat permintaan pembatalan order dari ' . $request->user()->name . '. Silahkan cek aplikasi Anda',
                    ''
                );
            }

            // Kirim notifikasi ke user
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
        $order->status = 'sedang diproses';
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
            'status' => ['required', Rule::in(['selesai', 'sedang diproses', 'batal', 'pesanan siap','menunggu pengembalian dana','pesanan diterima','sedang diantar'])],
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
        } elseif ($request->status == 'sedang diproses') {
            $this->firebaseService->sendNotification($userNotificationToken, 'Pesanan anda sedang diproses', 'Pesanan anda dengan Order ID ' . $order->id . ' sedang diproses', '');
        } elseif ($request->status == 'menunggu pengembalian dana') {
            $this->firebaseService->sendNotification($userNotificationToken, 'Permintaan pengembalian dana', 'Permintaan pengembalian dana anda dengan Order ID ' . $order->id . ' sedang diproses', '');
        }elseif ($request->status == 'konfirmasi pesanan') {
            $this->firebaseService->sendNotification($userNotificationToken, 'Tolong untuk senantiasa mengecek pesanan anda ', 'Pesanan anda dengan Order ID ' . $order->id . ' telah dikonfirmasi', '');
        }elseif ($request->status == 'sedang diantar') {
            $this->firebaseService->sendNotification($userNotificationToken, 'Pesanan anda sedang diantar', 'Pesanan anda dengan Order ID ' . $order->id . ' sedang diantar ke alamat tujuan', '');
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
        $order = Order::with(['orderDetails.menu', 'history.user'])
            ->where('id', $id)
            ->first();

        if (is_null($order)) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Jika metode order adalah 'delivery', tambahkan alamat yang aktif
        $alamatAktif = null;
        if ($order->order_method === 'delivery') {
            $alamatAktif = AlamatUser::where('user_id', $order->user_id)
                ->where('is_selected', true) // Ambil hanya alamat yang aktif
                ->first();
        }

        return response()->json([
            'success' => true,
            'message' => 'Order details retrieved successfully',
            'data' => [
                'order' => $order,
                'alamat' => $alamatAktif // Tambahkan alamat yang aktif
            ],
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

    public function filterbystatues(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'status' => ['required', Rule::in([
                'selesai', 'sedang diproses', 'batal', 'pesanan siap', 
                'menunggu pembayaran', 'menunggu pengembalian dana', 'konfirmasi pesanan'
            ])],
        ]);

        $query = Order::where('status', $request->status)
            ->where('user_id', $user->id);
        
        // If the status is 'konfirmasi pesanan' or 'sedang diproses', order by created_at ascending
        if (in_array($request->status, ['konfirmasi pesanan', 'sedang diproses'])) {
            $query->orderBy('created_at', 'asc');
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
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
