<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetailTopping extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_detail_id', 'topping_id', 'quantity'
    ];

    public function topping()
    {
        return $this->belongsTo(Topping::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }
    
    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class);
    }
}
