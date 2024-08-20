<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{

    use HasFactory;

    protected $appends = ['average_rating'];

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

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function getAverageRatingAttribute()
    {
        $userId = request()->query('user_id');
        return $this->averageRating($userId);
    }

    // Method to calculate the average rating, optionally filtered by user_id
    public function averageRating($userId = null)
    {
        $query = $this->ratings();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->avg('rating');
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
