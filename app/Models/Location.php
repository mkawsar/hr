<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'address',
        'latitude',
        'longitude',
        'radius_meters',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'active' => 'boolean',
        ];
    }

    public function clockInAttendance()
    {
        return $this->hasMany(DailyAttendance::class, 'clock_in_location_id');
    }

    public function clockOutAttendance()
    {
        return $this->hasMany(DailyAttendance::class, 'clock_out_location_id');
    }
}