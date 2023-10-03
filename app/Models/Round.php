<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    use HasFactory;

    protected $fillable = [
        'chain_id',
        'round_addr',
        'flagged_at',
        'amount_usd',
        'votes',
        'token',
        'match_amount',
        'match_amount_usd',
        'unique_contributors',
        'application_meta_ptr',
        'meta_ptr',
        'program_contract_address',
        'applications_start_time',
        'applications_end_time',
        'round_start_time',
        'round_end_time',
        'created_at_block',
        'updated_at_block',
        'metadata',
        'name',
        'prompt_data',
    ];

    // metadata should be returned as json
    protected $casts = [
        'metadata' => 'array',
        'prompt_data' => 'array',
    ];

    public function chain()
    {
        return $this->belongsTo(Chain::class);
    }

    public function applicationMetadata()
    {
        return $this->hasOne(RoundApplicationMetadata::class);
    }

    public function metadata()
    {
        return $this->hasOne(RoundMetadata::class);
    }

    public function requirements()
    {
        return $this->hasMany(RoundRequirement::class);
    }

    public function questions()
    {
        return $this->hasMany(RoundQuestion::class);
    }

    public function prompt()
    {
        return $this->hasOne(RoundPrompt::class);
    }

    // a Round connects to a Project via RoundApplications with Project.id_addr = RoundApplication.eth_addr
    public function projects()
    {
        return $this->hasManyThrough(
            Project::class,
            RoundApplication::class,
            'round_id',  // Foreign key on RoundApplication table...
            'id_addr',   // Foreign key on Project table...
            'id',        // Local key on Round table...
            'project_addr'  // Local key on RoundApplication table...
        );
    }
}
