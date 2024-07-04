<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $primaryKey = 'cart_id';

    protected $fillable = [
        'user_id',
        'menuID',
        'quantity',
        'date_item_menu',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menuID', 'menuID');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
