<?php

namespace App\Models;

use App\Models\Traits\ShortUniqueUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoundRole extends Model
{
    use HasFactory, ShortUniqueUuidTrait;

    protected $fillable = [
        'uuid',
        'round_id',
        'user_id',
        'role',
        'address',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($roundRole) {
            // Check if we have a user with this address
            if ($roundRole->user_id === null) {
                $user = User::where('eth_addr', $roundRole->address)->first();
                if (!$user) {
                    $user = User::create([
                        'eth_addr' => $roundRole->address,
                    ]);
                }
                $roundRole->user_id = $user->id;
                $roundRole->save();
            }
        });
    }


    public function round()
    {
        return $this->belongsTo(Round::class);
    }


    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
