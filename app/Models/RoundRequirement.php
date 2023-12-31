<?php

namespace App\Models;

use App\Models\Traits\ShortUniqueUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoundRequirement extends Model
{
    use HasFactory, ShortUniqueUuidTrait;

    protected $fillable = [
        'uuid',
        'application_metadata_id',
        'requirement',
    ];

    public function applicationMetadata()
    {
        return $this->belongsTo(RoundApplicationMetadata::class);
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

    public function question()
    {
        return $this->belongsTo(RoundQuestion::class);
    }

    public function roundQuestion()
    {
        return $this->belongsTo(RoundQuestion::class);
    }

    public function roundQuestionAnswer()
    {
        return $this->belongsTo(RoundQuestionAnswer::class);
    }

    public function roundQuestionAnswerMetadata()
    {
        return $this->belongsTo(RoundQuestionAnswerMetadata::class);
    }

    public function roundQuestionAnswerMetadataValue()
    {
        return $this->belongsTo(RoundQuestionAnswerMetadataValue::class);
    }

    public function roundQuestionAnswerValue()
    {
        return $this->belongsTo(RoundQuestionAnswerValue::class);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
