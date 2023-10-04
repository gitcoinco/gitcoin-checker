<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessControl extends Model
{
    use HasFactory;

    protected $fillable = [
        'eth_addr',
        'role',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'eth_addr', 'eth_addr');
    }
}
