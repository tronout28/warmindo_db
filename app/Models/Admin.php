<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'profile_picture',
        'username',
        'phone_number',
        'user_verified',
        'email',
        'password',
        'latitude',
        'longitude',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function orders()
    {
        return $this->belongsTo(Order::class);
    }
    
    public function getProfilePictureUrlAttribute()
    {
        return url('storage/profile_pictures/' . $this->profile_picture);
    }

    public function alamatUser()
    {
        return $this->belongsTo(AlamatUser::class, 'alamat_users_id');
    }
}
