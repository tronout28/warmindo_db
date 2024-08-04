<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'carts';

    protected $fillable = [
         'menu_id', 'quantity', 'price', 'variant_id'
    ];

    protected $casts = [
        'id' => 'integer',
        'menu_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'integer',
        'variant_id' => 'integer',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function cartToppings()
    {
        return $this->hasMany(CartTopping::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }
}
