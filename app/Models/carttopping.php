<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class carttopping extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id', 'topping_id', 'quantity'
    ];

    public function topping()
    {
        return $this->belongsTo(Topping::class);
    }

    public function CartsTopping()
    {
        return $this->hasMany(Cart::class);
    }

    public function Carttopping()
    {
        return $this->belongsTo(Cart::class);
    }
}
