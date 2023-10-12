<?php

namespace App\Models;

use App\Models\Traits\ShortUniqueUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoundPrompt extends Model
{
    use HasFactory, SoftDeletes, ShortUniqueUuidTrait;


    protected $fillable = [
        'uuid',
        'round_id',
        'system_prompt',
        'prompt',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function applications()
    {
        return $this->hasMany(RoundApplication::class);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
