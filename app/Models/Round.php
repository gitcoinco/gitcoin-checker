<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\ShortUniqueUuidTrait;
use Laravel\Scout\Searchable;

class Round extends Model
{
    use HasFactory, ShortUniqueUuidTrait, Searchable;

    protected $fillable = [
        'uuid',
        'chain_id',
        'round_addr',
        'flagged_at',
        'total_amount_donated_in_usd',
        'total_donations_count',
        'match_token_address',
        'match_amount',
        'match_amount_in_usd',
        'unique_contributors',
        'application_meta_ptr',
        'meta_ptr',
        'program_contract_address',
        'applications_start_time',
        'applications_end_time',
        'donations_start_time',
        'donations_end_time',
        'created_at_block',
        'updated_at_block',
        'round_metadata',
        'application_metadata',
        'name',
        'prompt_data',
        'funded_amount_in_usd',
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

    public function roundMetadata()
    {
        return $this->hasOne(RoundMetadata::class);
    }

    public function roundRoles()
    {
        return $this->hasMany(RoundRole::class);
    }

    public function requirements()
    {
        return $this->hasMany(RoundRequirement::class);
    }

    public function evaluationQuestions()
    {
        return $this->hasOne(RoundApplicationEvaluationQuestions::class);
    }

    public function gptRoundEligibilityScores()
    {
        return $this->hasMany(GptRoundEligibilityScore::class);
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

    public function applications()
    {
        return $this->hasMany(RoundApplication::class);
    }

    public function toSearchableArray()
    {
        return [
            'name' => $this->name,
            'year' => date('Y', strtotime($this->applications_start_time)),
            'round_addr' => $this->round_addr,
            'match_token_address' => $this->match_token_address,
            'chain_id' => $this->chain_id,
            'chain' => $this->chain->name,
            'applications_year' => date('Y', strtotime($this->applications_start_time)),
            'donations_year' => date('Y', strtotime($this->donations_start_time)),
        ];
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
