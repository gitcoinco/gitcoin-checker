<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chain extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'chain_id',
        'name',
        'number',
    ];

    public function rounds()
    {
        return $this->hasMany(Round::class);
    }
}
