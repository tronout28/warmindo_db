<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_type',
        'external_id',
        'payment_method',
        'status',
        'amount',
        'payment_channel',
        'description',
        'payment_id',
        'paid_at',
        'order_id'
    ];
}
