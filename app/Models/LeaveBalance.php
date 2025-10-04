<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'year',
        'balance',
        'consumed',
        'accrued',
        'carry_forward',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:1',
            'consumed' => 'decimal:1',
            'accrued' => 'decimal:1',
            'carry_forward' => 'decimal:1',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}