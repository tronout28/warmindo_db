<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'menu_id', 'quantity', 'price'
    ];

    protected $casts = [
        'id' => 'integer',
        'order_id' => 'integer',
        'menu_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'integer',
        'notes' => 'string',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function toppings() {
        return $this->hasMany(OrderDetailTopping::class);
    }
}
