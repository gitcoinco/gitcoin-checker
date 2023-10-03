<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoundPrompt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'round_id',
        'system_prompt',
        'prompt',
    ];
}
