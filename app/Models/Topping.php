<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topping extends Model
{
    use HasFactory;

    protected $primaryKey = 'topping_id';

    protected $fillable = [
        'name_topping',
        'price',
        'image',
        'stock',
    ];

    public function menus()
    {
        return $this->hasMany(Menu::class, 'topping_id', 'topping_id');
    }
}
