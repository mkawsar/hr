<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Main Office',
                'type' => 'office',
                'address' => '123 Business Street, City Center',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'radius_meters' => 100,
                'active' => true,
            ],
            [
                'name' => 'Branch Office',
                'type' => 'office',
                'address' => '456 Corporate Avenue, Downtown',
                'latitude' => 40.7589,
                'longitude' => -73.9851,
                'radius_meters' => 100,
                'active' => true,
            ],
            [
                'name' => 'Remote Work',
                'type' => 'remote',
                'address' => 'Work from Home',
                'latitude' => null,
                'longitude' => null,
                'radius_meters' => 0,
                'active' => true,
            ],
            [
                'name' => 'Field Office',
                'type' => 'field',
                'address' => '789 Industrial Zone',
                'latitude' => 40.6892,
                'longitude' => -74.0445,
                'radius_meters' => 200,
                'active' => true,
            ],
        ];

        foreach ($locations as $location) {
            Location::firstOrCreate(
                ['name' => $location['name']],
                $location
            );
        }
    }
}