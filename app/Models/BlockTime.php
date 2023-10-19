<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'chain_id',
        'block_number',
        'timestamp',
        'is_estimate',
    ];
}
