<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    use HasFactory;

    protected $table = 'history';
    protected $primaryKey = 'history_id';

    protected $fillable = [
        'order_id',
        'status',
        'change_date'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
