<?php

namespace App\Http\Controllers;

require_once base_path('/vendor/autoload.php');

use App\Http\Requests\PaymentRequest;
use App\Models\Order;
use App\Services\XenditService;
use App\Models\Payment;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;


class PaymentController extends Controller
{
    protected $xenditService;
    protected $firebaseService;
    public function __construct(XenditService $xenditService, FirebaseService $firebaseService)
    {
        $this->xenditService = $xenditService;
        $this->firebaseService = $firebaseService;
    }

    public function createPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
        ]);
        $user = auth()->user();
        $external_id = (string) date('YmdHis');
        $description = 'Bayar Makan Anjay';
        $order = Order::where('id', $request->order_id)->first();   
        $amount = $order->price_order;

        $transaction = Payment::where('order_id', $order->id)->first();
        if ($transaction != null && $transaction->status == 'pending') {
            return response([
                'status' => 'success',
                'message' => 'Payment created successfully',
                'checkout_link' => $transaction->checkout_link,
            ], 201);
        }
        if ($transaction != null && $transaction->status == 'paid') {
            return response([
                'status' => 'failed',
                'message' => 'Payment already paid',
            ], 400);
        }
        $options = [
            'external_id' => $external_id,
            'description' => $description,
            'amount' => $amount,
            'currency' => 'IDR',
        ];
        $response = $this->xenditService->createInvoice($options);

        $payment = new Payment();
        $payment->status = 'pending';
        $payment->invoice_id = $response['id'];
        $payment->checkout_link = $response['invoice_url'];
        $payment->external_id = $external_id;
        $payment->user_id = $user->id;
        $payment->order_id = $order->id;
        $payment->save();

        // send notification to user
        $expiredDate = Carbon::parse($response['expiry_date']);
        $description = 'Menunggu pembayaran laundry  ' . $order->no_pemesanan . ' ' . '. Bayar sebelum tamggal ' . $expiredDate->format('d F Y') . ' pukul ' . $expiredDate->format('H:i') . ' WIB';
        $this->firebaseService->sendNotification($payment->user->notification_token, 'Menunggu Pembayaran', 'Tenang kamu bisa membuat pembayaran lagi', '');
        return response([
            'status' => 'success',
            'message' => 'Payment created successfully',
            'checkout_link' => $response['invoice_url'],
            'description' => $description,
        ], 201);
    }

    public function expirePayment($id)
    {
        $payment = Payment::where('order_id', $id)->first();
        if ($payment == null) {
            return response([
                'status' => 'failed',
                'message' => 'Payment not found',
            ], 404);
        }
        if($payment->status == 'expired') {
            return response([
                'status' => 'failed',
                'message' => 'Payment already expired',
            ], 400);
        }
        $this->xenditService->expireInvoice($payment->invoice_id);
        $payment->status = 'expired';
        $payment->save();

        return response([
            'status' => 'success',
            'message' => 'Payment expired',
        ], 200);
    }

    public function updatePaymentStatus(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
        ]);
        $payment = Payment::where('order_id', $request->order_id)->first();
        if ($payment == null) {
            return response([
                'status' => 'failed',
                'message' => 'Payment not found',
            ], 404);
        }
        $response = $this->xenditService->getInvoice($payment->invoice_id);

        $payment->status = strtolower($response['status']);
        $payment->save();

        return response([
            'status' => 'success',
            'message' => 'Payment status updated',
            'payment_status' => $payment->status,
        ], 200);
    }

    public function invoiceStatus(Request $request)
    {
        $payment = Payment::where('external_id', $request->external_id)->first();
        if ($payment == null) {
            return response([
                'status' => 'failed',
                'message' => 'Payment not found',
            ], 404);
        }
        $payment->status = strtolower($request->status);
        $payment->save();
        return response([
            'status' => 'success',
            'message' => 'Payment status updated',
            'payment_status' => $payment->status,
        ], 200);
    }

    public function getInvoiceUser(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
        ]);
        $payment = Payment::where('order_id', $request->order_id)->first();
        if ($payment == null) {
            return response([
                'status' => 'failed',
                'message' => 'Payment not found. You can create payment first',
            ], 404);
        }
        return response([
            'status' => 'success',
            'message' => 'Payment for order ' . $payment->order->nama_pemesan,
            'payment' => $payment->only([
                'status',
                'checkout_link',
                'external_id',
                'user_id',
                'order_id'
            ]),
        ], 200);
    }
}