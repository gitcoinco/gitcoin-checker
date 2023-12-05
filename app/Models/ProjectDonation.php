<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectDonation extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'internal_application_id',
        'application_id', // This comes from the applications.json file
        'round_id',
        'amount_usd',
        'transaction_addr',
        'voter_addr',
        'block_number',
        'grant_addr',
    ];

    public function application()
    {
        return $this->belongsTo(RoundApplication::class, 'id', 'internal_application_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }
}
