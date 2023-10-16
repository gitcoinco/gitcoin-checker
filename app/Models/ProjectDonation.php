<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectDonation extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'application_id',
        'round_id',
        'amount_usd',
        'transaction_addr',
        'voter_addr',
        'block_number',
        'grant_addr',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
