<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\ShortUniqueUuidTrait;

class Chain extends Model
{
    use HasFactory, ShortUniqueUuidTrait;

    protected $fillable = [
        'uuid',
        'chain_id',
        'name',
        'number',
        'rpc_endpoint',
    ];

    public function rounds()
    {
        return $this->hasMany(Round::class);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
