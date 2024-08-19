<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{

    use HasFactory;

    protected $fillable = [
        'image',
        'name_menu',
        'price',
        'category',
        'second_category', // Add the new column here
        'stock',
        'rating',
        'description',
    ];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($image) => url('/menu/'.$image),
        );
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function updateAverageRating()
    {
        $averageRating = $this->orderDetails()->avg('rating');
        $this->rating = $averageRating;
        $this->save();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }
}
