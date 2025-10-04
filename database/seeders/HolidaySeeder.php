<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = now()->year;
        
        $holidays = [
            // National Holidays
            [
                'name' => 'New Year Day',
                'description' => 'New Year celebration',
                'date' => Carbon::create($currentYear, 1, 1),
                'type' => 'national',
                'recurring' => true,
                'active' => true,
            ],
            [
                'name' => 'Independence Day',
                'description' => 'National Independence Day',
                'date' => Carbon::create($currentYear, 3, 26),
                'type' => 'national',
                'recurring' => true,
                'active' => true,
            ],
            [
                'name' => 'Victory Day',
                'description' => 'Victory Day celebration',
                'date' => Carbon::create($currentYear, 12, 16),
                'type' => 'national',
                'recurring' => true,
                'active' => true,
            ],
            [
                'name' => 'Eid-ul-Fitr',
                'description' => 'End of Ramadan celebration',
                'date' => Carbon::create($currentYear, 4, 10), // Approximate date
                'type' => 'national',
                'recurring' => false,
                'active' => true,
            ],
            [
                'name' => 'Eid-ul-Adha',
                'description' => 'Festival of Sacrifice',
                'date' => Carbon::create($currentYear, 6, 16), // Approximate date
                'type' => 'national',
                'recurring' => false,
                'active' => true,
            ],
            [
                'name' => 'Durga Puja',
                'description' => 'Hindu festival celebration',
                'date' => Carbon::create($currentYear, 10, 12), // Approximate date
                'type' => 'national',
                'recurring' => false,
                'active' => true,
            ],
            [
                'name' => 'Christmas Day',
                'description' => 'Christmas celebration',
                'date' => Carbon::create($currentYear, 12, 25),
                'type' => 'national',
                'recurring' => true,
                'active' => true,
            ],

            // Company Holidays
            [
                'name' => 'Company Foundation Day',
                'description' => 'Company anniversary celebration',
                'date' => Carbon::create($currentYear, 7, 15),
                'type' => 'company',
                'recurring' => true,
                'active' => true,
            ],
            [
                'name' => 'Annual Company Retreat',
                'description' => 'Company team building retreat',
                'date' => Carbon::create($currentYear, 8, 20),
                'type' => 'company',
                'recurring' => false,
                'active' => true,
            ],
        ];

        foreach ($holidays as $holiday) {
            Holiday::firstOrCreate(
                [
                    'name' => $holiday['name'],
                    'date' => $holiday['date'],
                ],
                $holiday
            );
        }
    }
}