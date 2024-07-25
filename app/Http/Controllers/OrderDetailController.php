<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderDetailController extends Controller
{
    public function createOrderDetail(Request $request)
    {
        $request->validate([
            'menu_id' => 'required|string|exists:menus,id',
            'quantity' => 'required|integer',
        ]);
    }
}
