<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    use HasFactory;

    protected $table = 'history';

    protected $fillable = [
        'order_id', 'menu_id', 'quantity', 'price', 'notes'
    ];

    protected $casts = [
        'id' => 'integer',
        'order_id' => 'integer',
        'menu_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'integer',
        'notes' => 'string',
    ];

        public function orderDetail()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
