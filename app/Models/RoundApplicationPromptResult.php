<?php

namespace App\Models;

use App\Http\Controllers\RoundApplicationPromptResultController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\ShortUniqueUuidTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoundApplicationPromptResult extends Model
{
    use HasFactory, ShortUniqueUuidTrait, SoftDeletes;

    protected $fillable = [
        'uuid',
        'application_id',
        'round_id',
        'project_id',
        'prompt_id',
        'prompt_type',
        'system_prompt',
        'prompt_data',
        'results_data',
        'score',
    ];


    public function application()
    {
        return $this->belongsTo(RoundApplication::class);
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function prompt()
    {
        return $this->belongsTo(RoundPrompt::class);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
