<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;


class Variant extends Model
{
    use HasFactory;

    protected $table = 'variants';

    // Kolom yang bisa diisi
    protected $fillable = [
        'name_varian',
        'category',
        'image',
        'stock_varian'
    ];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($image) => url('/variant/'.$image),
        );
    }

    // Kolom yang akan disembunyikan dari array atau JSON
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function orderDetails()
    {
        return $this->belongsTo(OrderDetail::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function toppings()
    {
        return $this->hasMany(Topping::class);
    }
}
