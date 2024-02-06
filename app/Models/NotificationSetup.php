<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationSetup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'medium',
        'additional_emails',
        'details',
        'include_applications',
        'include_rounds',
        'summary_frequency',
    ];

    public function logs()
    {
        return $this->hasMany(NotificationLog::class, 'notification_id');
    }

    public function applications()
    {
        return $this->belongsToMany(RoundApplication::class, 'notification_log_applications');
    }

    public function rounds()
    {
        return $this->belongsToMany(Round::class, 'notification_log_rounds');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
