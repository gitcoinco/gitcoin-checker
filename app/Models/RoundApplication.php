<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoundApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'round_id',
        'project_addr',
        'status',
        'last_updated_on',
        'version',
        'metadata',
    ];
}
