<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectOwner extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'eth_addr',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
