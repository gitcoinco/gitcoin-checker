<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'notification_id',
        'subject',
        'message',
    ];

    public function applications()
    {
        return $this->belongsToMany(RoundApplication::class, 'notification_log_applications');
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
