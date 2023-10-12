<?php

namespace App\Models;

use App\Models\Traits\ShortUniqueUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoundQuestion extends Model
{
    use HasFactory, ShortUniqueUuidTrait;

    protected $fillable = [
        'uuid',
        'application_metadata_id',
        'question_id',
        'title',
        'type',
        'required',
        'info',
        'hidden',
        'encrypted',
    ];

    public function applicationMetadata()
    {
        return $this->belongsTo(RoundApplicationMetadata::class);
    }

    public function question()
    {
        return $this->belongsTo(RoundQuestion::class);
    }

    public function answers()
    {
        return $this->hasMany(RoundQuestionAnswer::class);
    }

    public function requirements()
    {
        return $this->hasMany(RoundRequirement::class);
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function roundMetadata()
    {
        return $this->belongsTo(RoundMetadata::class);
    }

    public function roundApplicationMetadata()
    {
        return $this->belongsTo(RoundApplicationMetadata::class);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
