<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlamatUser extends Model
{
    use HasFactory;

    protected $table = 'alamat_user';

    protected $fillable = [
        'user_id', 
        'nama_alamat', 
        'nama_kost', 
        'detail_alamat',
        'catatan_alamat',
        'longitude',
        'latitude',
        'radius_km',
        'is_selected'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'alamat_users_id');
    }

    public function admins()
    {
        return $this->hasMany(Admin::class);
    }
}
