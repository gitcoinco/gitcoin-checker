<?php

namespace App\Models;

use App\Models\Traits\ShortUniqueUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationSetup extends Model
{
    use HasFactory, SoftDeletes, ShortUniqueUuidTrait;

    protected $fillable = [
        'user_id',
        'title',
        'email_subject',
        'additional_emails',
        'details',
        'include_applications',
        'include_rounds',
        'days_of_the_week',
        'time_type',
        'time_of_the_day',
        'nr_summaries_per_email',
    ];

    public function logs()
    {
        return $this->hasMany(NotificationLog::class, 'notification_id');
    }

    public function notificationLogApplications()
    {
        return $this->hasMany(NotificationLogApplications::class, 'notification_setup_id');
    }

    public function notificationLogRounds()
    {
        return $this->hasMany(NotificationLogRound::class, 'notification_setup_id');
    }



    public function notificationSetupRounds()
    {
        return $this->hasMany(NotificationSetupRound::class, 'notification_setup_id');
    }

    // public function applications()
    // {
    //     return $this->belongsToMany(RoundApplication::class, 'notification_log_applications');
    // }

    // public function rounds()
    // {
    //     return $this->belongsToMany(Round::class, 'notification_log_rounds');
    // }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getDaysOfTheWeekAttribute($value)
    {
        return json_decode($value);
    }


    public function setDaysOfTheWeekAttribute($value)
    {
        $this->attributes['days_of_the_week'] = json_encode($value);
    }

    public function getAdditionalEmailsAttribute($value)
    {
        return json_decode($value);
    }

    public function setAdditionalEmailsAttribute($value)
    {
        $this->attributes['additional_emails'] = json_encode($value);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
