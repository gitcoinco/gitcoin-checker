<?php

namespace App\Models;

use App\Models\Traits\ShortUniqueUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoundApplicationEvaluationQuestions extends Model
{
    use HasFactory, ShortUniqueUuidTrait;

    protected $fillable = [
        'uuid',
        'round_id',
        'questions',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function evaluationAnswers()
    {
        return $this->hasMany(RoundApplicationEvaluationAnswers::class);
    }


    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
