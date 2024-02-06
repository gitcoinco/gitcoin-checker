<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationSetupRound extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'notification_setup_id',
        'round_id',
    ];

    public function notificationSetup()
    {
        return $this->belongsTo(NotificationSetup::class, 'notification_setup_id');
    }

    public function round()
    {
        return $this->belongsTo(Round::class, 'round_id');
    }

    public function notificationLogs()
    {
        return $this->hasMany(NotificationLog::class, 'notification_setup_round_id');
    }
}
