<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_open',
        'days',
        'hours',
        'temporary_closure_duration',
    ];
}
