<?php

namespace App\Models;

use App\Models\Traits\ShortUniqueUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoundApplicationUserScore extends Model
{
    use HasFactory, SoftDeletes, ShortUniqueUuidTrait;

    protected $fillable = [
        'user_id',
        'application_id',
        'round_id',
        'score',
        'notes',
    ];

    public function application()
    {
        return $this->belongsTo(RoundApplication::class, 'application_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
