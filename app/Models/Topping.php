<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;


class Topping extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_topping',
        'price',
        'stock_topping',
        // 'menu_id',
    ];

    protected $casts = [
        'price' => 'integer',
    ];


    public function menus()
    {
        return $this->hasMany(Menu::class, 'topping_id', 'topping_id');
    }
}
