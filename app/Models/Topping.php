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
        'status_topping',
        // 'menu_id',
    ];

    protected $casts = [
        'price' => 'integer',
    ];


    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menu_topping');   
    }
}
