<?php

namespace App\Models;

use App\Http\Controllers\RoundApplicationController;
use App\Models\Traits\ShortUniqueUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoundApplicationEvaluationAnswers extends Model
{
    use HasFactory, ShortUniqueUuidTrait;

    protected $fillable = [
        'uuid',
        'round_id',
        'application_id',
        'user_id',
        'questions',
        'answers',
        'notes',
    ];

    protected static function booted()
    {
        static::saved(function ($model) {
            RoundApplicationController::updateScore($model->application);
        });
    }



    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function application()
    {
        return $this->belongsTo(RoundApplication::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
