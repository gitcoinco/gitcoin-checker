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
}
