<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $primaryKey = 'order_id';

    protected $fillable = [
        'user_id',
        'menuID',
        'price_order',
        'order_date',
        'status',
        'payment',
        'refund',
        'note',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the menu that belongs to the order.
     */
    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
