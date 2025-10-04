<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'encashable',
        'carry_forward_allowed',
        'max_carry_forward_days',
        'accrual_days_per_year',
        'accrual_frequency',
        'requires_approval',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'encashable' => 'boolean',
            'carry_forward_allowed' => 'boolean',
            'requires_approval' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }
}