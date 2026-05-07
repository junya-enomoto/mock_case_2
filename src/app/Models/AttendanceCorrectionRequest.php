<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    protected $table = 'correction';

    protected $fillable = [
        'attendance_id',
        'clock_in_new',
        'clock_out_new',
        'remarks',
        'status',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->hasOneThrough(User::class, Attendance::class, 'id', 'id', 'attendance_id', 'user_id');
    }

    public function correctionRests() 
    {
        return $this->hasMany(CorrectionRest::class, 'correction_id');
    }
}
