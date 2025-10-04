<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeductionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'threshold_minutes',
        'deduction_value',
        'deduction_unit',
        'description',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'deduction_value' => 'decimal:2',
            'active' => 'boolean',
        ];
    }
}