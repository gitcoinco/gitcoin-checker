<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\ShortUniqueUuidTrait;

class RoundApplication extends Model
{
    use HasFactory, ShortUniqueUuidTrait;

    protected $fillable = [
        'uuid',
        'round_id',
        'application_id', // The id of the application from the applications.json file
        'project_addr',
        'status',
        'last_updated_on',
        'version',
        'metadata',
        'approved_at',
        'rejected_at',
        'created_at',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_addr', 'id_addr');
    }

    public function results()
    {
        return $this->hasMany(RoundApplicationPromptResult::class, 'application_id', 'id');
    }

    public function prompt()
    {
        return $this->belongsTo(RoundPrompt::class, 'prompt_id', 'id');
    }

    public function latestPrompt()
    {
        return $this->hasOne(RoundPrompt::class, 'round_id', 'round_id')->orderBy('id', 'desc');
    }

    public function evaluationAnswers()
    {
        return $this->hasMany(RoundApplicationEvaluationAnswers::class, 'application_id', 'id');
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
