<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_out_latitude',
        'clock_out_longitude',
        'clock_in_location_id',
        'clock_out_location_id',
        'source',
        'status',
        'late_minutes',
        'early_minutes',
        'deduction_amount',
        'adjusted_by',
        'adjustment_reason',
        'adjusted_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'clock_in_latitude' => 'decimal:8',
            'clock_in_longitude' => 'decimal:8',
            'clock_out_latitude' => 'decimal:8',
            'clock_out_longitude' => 'decimal:8',
            'deduction_amount' => 'decimal:2',
            'adjusted_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clockInLocation()
    {
        return $this->belongsTo(Location::class, 'clock_in_location_id');
    }

    public function clockOutLocation()
    {
        return $this->belongsTo(Location::class, 'clock_out_location_id');
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}