<?php

namespace App\Models;

use App\Models\Traits\ShortUniqueUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessControl extends Model
{
    use HasFactory, ShortUniqueUuidTrait;

    protected $fillable = [
        'uuid',
        'eth_addr',
        'role',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'eth_addr', 'eth_addr');
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
