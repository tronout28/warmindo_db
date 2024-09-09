<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'menu_id',
        'price_order',
        'status',
        'payment_method',
        'order_method',
        'note',
        'admin_fee',  
    ];

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function history()
    {
        return $this->hasMany(History::class);
    }

    public function transactions()
    {
        return $this->hasOne(Transaction::class);
    }
}
