<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_addr',
        'title',
        'project_number',
        'meta_ptr',
        'metadata',
        'owners',
        'created_at_block',
    ];

    protected $casts = [
        'metadata' => 'array',
        'owners' => 'array',
    ];


    public function applications()
    {
        return $this->hasMany(RoundApplication::class, 'project_addr', 'id_addr');
    }
}
