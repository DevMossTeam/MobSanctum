<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pesan extends Model
{
    protected $table = 'pesan';

    public $timestamps = false; // Karena pakai created_at manual

    protected $fillable = [
        'id',
        'user_id',
        'pesan',
        'created_at',
        'status_read',
        'status',
        'detail_pesan',
        'pesan_type',
        'item_id',
        'nama',
        'email',
    ];

    public $incrementing = false; 
    protected $keyType = 'string';
}
