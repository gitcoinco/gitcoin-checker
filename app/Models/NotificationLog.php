<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'notification_setup_id',
        'subject',
        'message',
    ];

    public function setup()
    {
        return $this->belongsTo(NotificationSetup::class, 'notification_setup_id');
    }

    public function notificationLogApplications()
    {
        return $this->hasMany(NotificationLogApplications::class, 'notification_log_id');
    }

    public function applications()
    {
        return $this->belongsTo(RoundApplication::class, 'round_id');
    }

    public function rounds()
    {
        return $this->belongsToMany(Round::class, 'notification_log_rounds');
    }

    public function notification()
    {
        return $this->belongsTo(NotificationSetup::class, 'notification_id');
    }
}
