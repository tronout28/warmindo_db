<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\Request;

class HistoryController extends Controller
{

    public function getHistory()
{
    // Fetch all history records, ordered by created_at in descending order (newest first)
    $histories = History::with('menu')->orderBy('created_at', 'desc')->get();

    return response()->json([
        'status' => 'success',
        'message' => 'History records retrieved successfully',
        'data' => $histories
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

    public function orderToHistory() 
    {

        $orders = Order::where('status', 'selesai')->orWhere('status', 'batal')->get();
        foreach ($orders as $order) {
            $orderDetails = OrderDetail::where('order_id', $order->id)->get();
            

            foreach ($orderDetails as $orderDetail) {
                History::create([
                    'order_id' => $orderDetail->order_id,
                    'menu_id' => $orderDetail->menu_id,
                    'quantity' => $orderDetail->quantity,
                    'price' => $orderDetail->price,
                    'notes' => $orderDetail->notes,
                ]);
            }
        }

        return response([
            'status' => 'success',
            'message' => 'History created and original orders deleted successfully',
        ]);
    }

}
