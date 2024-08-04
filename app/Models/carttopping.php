<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartTopping extends Model
{
    use HasFactory;

    protected $table = 'cart_toppings';

    protected $fillable = [
        'cart_id', 'topping_id', 'quantity',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }
    
    

    public function topping()
    {
        return $this->belongsTo(Topping::class);
    }
}
