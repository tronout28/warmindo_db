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
        'image',
        'stock_topping',
        'menu_id',
    ];

    protected $casts = [
        'price' => 'integer',
    ];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($image) => url('/topping/'.$image),
        );
    }


    public function menus()
    {
        return $this->hasMany(Menu::class, 'topping_id', 'topping_id');
    }
}
