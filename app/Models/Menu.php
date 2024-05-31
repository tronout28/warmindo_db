<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'menuID'; // Specify the custom primary key

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'image',
        'name_menu',
        'price',
        'category',
        'stock',
        'ratings',
        'description',
    ];

    /**
     * image
     */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($image) => url('/menu/'.$image),
        );
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
