<?php

namespace App\Models;

use App\Models\Traits\ShortUniqueUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GptRoundEligibilityScore extends Model
{
    use HasFactory, ShortUniqueUuidTrait, SoftDeletes;

    protected $fillable = [
        'uuid',
        'round_id',
        'gpt_prompt',
        'gpt_response',
        'score',
        'reason',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
