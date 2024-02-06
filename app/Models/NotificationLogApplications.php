<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationLogApplications extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'notification_log_id',
        'application_id',
    ];

    public function notificationLog()
    {
        return $this->belongsTo(NotificationLog::class, 'notification_log_id');
    }

    public function application()
    {
        return $this->belongsTo(RoundApplication::class, 'application_id');
    }

    public function round()
    {
        return $this->belongsTo(Round::class, 'round_id');
    }

    public function notification()
    {
        return $this->belongsTo(NotificationSetup::class, 'notification_id');
    }
}
