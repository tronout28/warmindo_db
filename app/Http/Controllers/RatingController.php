<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\OrderDetail;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{
    public function rateMenuItem(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'rating' => 'required|numeric|min:0|max:5',
            'menu_id' => 'required|exists:menus,id',
            'order_detail_id' => 'required|exists:order_details,id',
        ]);
        $orderDetail = OrderDetail::find($request->order_detail_id);

        if (!$orderDetail) {
            return response()->json(['message' => 'Order detail not found'], 404);
        }

        // Create or update the rating for the menu item in the order detail
        $rating = Rating::create([
            'order_detail_id' => $request->order_detail_id,
            'menu_id' => $request->menu_id,
            'rating' => $request->rating,
            'user_id' => $user->id,
        ]);

        // Recalculate and round the average rating for the menu item
        $averageRating = Rating::where('menu_id', $request->menu_id)->avg('rating');
        $roundedAverageRating = round($averageRating, 1);

        // Update the menu item with the rounded average rating
        $menu = Menu::find($request->menu_id);
        $menu->rating = $roundedAverageRating;
        $menu->save();

        return response()->json([
            'success' => true,
            'message' => 'Menu item rated successfully',
            'data' => $roundedAverageRating,
        ], 200);
    }

}
