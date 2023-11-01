<?php

namespace App\Models;

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
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
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
