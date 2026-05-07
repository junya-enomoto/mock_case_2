<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rests()
    {
        return $this->hasMany(Rest::class);
    }

    public function getTotalBreakMinutesAttribute(): int
    {
        $totalMinutes = 0;
        foreach ($this->rests as $rest) {
            if ($rest->start_time && $rest->end_time) {
                $start = Carbon::parse($rest->start_time);
                $end = Carbon::parse($rest->end_time);
                $totalMinutes += $start->diffInMinutes($end);
            }
        }

        return $totalMinutes;
    }

    public function getTotalBreakTimeAttribute(): ?string
    {
        $minutes = $this->total_break_minutes;

        if ($minutes <= 0) {
            return null;
        }

        $h = floor($minutes / 60);
        $m = $minutes % 60;

        return sprintf('%d:%02d', $h, $m);
    }

    public function getTotalWorkMinutesAttribute(): int
    {

        if (! $this->clock_in || ! $this->clock_out) {
            return 0;
        }

        $start = Carbon::parse($this->clock_in);
        $end = Carbon::parse($this->clock_out);

        $stayMinutes = $start->diffInMinutes($end);

        $workMinutes = $stayMinutes - $this->total_break_minutes;

        return max(0, $workMinutes);
    }

    public function getTotalWorkTimeAttribute(): ?string
    {
        $minutes = $this->total_work_minutes;

        if ($minutes <= 0) {
            return null;
        }

        $h = floor($minutes / 60);
        $m = $minutes % 60;

        return sprintf('%d:%02d', $h, $m);
    }

    public function correctionRequests()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }
}
