<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftSwap extends Model
{
    protected $fillable = [
        'schedule_period_id',
        'requester_id',
        'responder_id',
        'requester_schedule_id',
        'responder_schedule_id',
        'status',
        'alasan',
        'catatan_pic',
    ];

    public function schedulePeriod()
    {
        return $this->belongsTo(SchedulePeriod::class);
    }

    public function requester()
    {
        return $this->belongsTo(Agent::class, 'requester_id');
    }

    public function responder()
    {
        return $this->belongsTo(Agent::class, 'responder_id');
    }

    public function requesterSchedule()
    {
        return $this->belongsTo(Schedule::class, 'requester_schedule_id');
    }

    public function responderSchedule()
    {
        return $this->belongsTo(Schedule::class, 'responder_schedule_id');
    }
}
