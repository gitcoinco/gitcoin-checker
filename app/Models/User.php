<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Traits\ShortUniqueUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use ShortUniqueUuidTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'eth_addr',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'is_admin',
        'is_round_operator',
    ];

    /**
     * Get the user's access control and bind on eth_addr.
     */
    public function accessControl()
    {
        return $this->hasOne(AccessControl::class, 'eth_addr', 'eth_addr');
    }

    public function preferences()
    {
        return $this->hasMany(UserPreference::class);
    }

    public function notificationSetups()
    {
        return $this->hasMany(NotificationSetup::class);
    }

    public function roundRoles()
    {
        return $this->hasMany(RoundRole::class);
    }

    public function getIsAdminAttribute()
    {
        return Cache::remember('User::accessControlExists.' . $this->id, 60, function () {
            return $this->accessControl()->exists();
        });
    }

    public function getIsRoundOperatorAttribute()
    {
        return Cache::remember('User::roundRolesExists.' . $this->id, 60, function () {
            return $this->roundRoles()->exists();
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
