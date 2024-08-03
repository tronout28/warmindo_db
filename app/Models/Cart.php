<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
         'menu_id', 'quantity', 'price'
    ];

    protected $casts = [
        'id' => 'integer',
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

    public function CartsTopping()
    {
        return $this->hasMany(Cart::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function toppings() {
        return $this->hasMany(carttopping::class);
    }
}
