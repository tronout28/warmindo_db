<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;


class Variant extends Model
{
    use HasFactory;

    // Nama tabel jika tidak mengikuti konvensi penamaan Laravel
    protected $table = 'variants';

    // Primary key dari tabel jika tidak menggunakan id
    protected $primaryKey = 'id_varian';

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
}
