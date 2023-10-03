<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoundApplicationPromptResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'round_id',
        'project_id',
        'prompt_id',
        'prompt_type',
        'prompt_data',
        'results_data',
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
}
