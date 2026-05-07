<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionRest extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_id',
        'original_rest_id',
        'start_time_new',
        'end_time_new',
    ];

    public function correction()
    {
        return $this->belongsTo(AttendanceCorrectionRequest::class);
    }

    public function originalRest()
    {
        return $this->belongsTo(Rest::class, 'original_rest_id');
    }
}
