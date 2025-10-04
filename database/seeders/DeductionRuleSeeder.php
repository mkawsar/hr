<?php

namespace Database\Seeders;

use App\Models\DeductionRule;
use Illuminate\Database\Seeder;

class DeductionRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deductionRules = [
            [
                'threshold_minutes' => 4,
                'deduction_value' => 0.5,
                'deduction_unit' => 'hours',
                'description' => '4 minutes late = 0.5 hours deduction',
                'active' => true,
            ],
            [
                'threshold_minutes' => 5,
                'deduction_value' => 1,
                'deduction_unit' => 'hours',
                'description' => '5 minutes late = 1 hour deduction',
                'active' => true,
            ],
            [
                'threshold_minutes' => 6,
                'deduction_value' => 1.5,
                'deduction_unit' => 'hours',
                'description' => '6 minutes late = 1.5 hours deduction',
                'active' => true,
            ],
            [
                'threshold_minutes' => 7,
                'deduction_value' => 2,
                'deduction_unit' => 'hours',
                'description' => '7 minutes late = 2 hours deduction',
                'active' => true,
            ],
            [
                'threshold_minutes' => 15,
                'deduction_value' => 0.5,
                'deduction_unit' => 'days',
                'description' => '15 minutes late = 0.5 days deduction',
                'active' => true,
            ],
            [
                'threshold_minutes' => 30,
                'deduction_value' => 1,
                'deduction_unit' => 'days',
                'description' => '30 minutes late = 1 day deduction',
                'active' => true,
            ],
        ];

        foreach ($deductionRules as $rule) {
            DeductionRule::firstOrCreate(
                [
                    'threshold_minutes' => $rule['threshold_minutes'],
                    'deduction_unit' => $rule['deduction_unit']
                ],
                $rule
            );
        }
    }
}