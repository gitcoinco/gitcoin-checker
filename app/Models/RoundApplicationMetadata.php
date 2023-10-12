<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\ShortUniqueUuidTrait;

class RoundApplicationMetadata extends Model
{
    use HasFactory, ShortUniqueUuidTrait;

    protected $fillable = [
        'uuid',
        'round_id',
        'last_updated_on',
        'version',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function questions()
    {
        return $this->hasMany(RoundQuestion::class);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
