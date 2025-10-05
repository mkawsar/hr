<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Get address from GPS coordinates using reverse geocoding
     */
    public static function getAddressFromCoordinates(float $latitude, float $longitude): ?string
    {
        try {
            // Using Nominatim (OpenStreetMap) free reverse geocoding service
            $response = Http::timeout(10)->get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'json',
                'lat' => $latitude,
                'lon' => $longitude,
                'zoom' => 18,
                'addressdetails' => 1,
                'accept-language' => 'en'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['display_name'])) {
                    return $data['display_name'];
                }
            }

            // Fallback: Try with a different service (BigDataCloud)
            return self::getAddressFromBigDataCloud($latitude, $longitude);

        } catch (\Exception $e) {
            Log::warning('Reverse geocoding failed', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage()
            ]);

            // Fallback: Try with a different service
            return self::getAddressFromBigDataCloud($latitude, $longitude);
        }
    }

    /**
     * Fallback geocoding service using BigDataCloud
     */
    private static function getAddressFromBigDataCloud(float $latitude, float $longitude): ?string
    {
        try {
            $response = Http::timeout(10)->get('https://api.bigdatacloud.net/data/reverse-geocode-client', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'localityLanguage' => 'en'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['locality']) || isset($data['city']) || isset($data['principalSubdivision'])) {
                    $addressParts = [];
                    
                    if (!empty($data['locality'])) {
                        $addressParts[] = $data['locality'];
                    } elseif (!empty($data['city'])) {
                        $addressParts[] = $data['city'];
                    }
                    
                    if (!empty($data['principalSubdivision'])) {
                        $addressParts[] = $data['principalSubdivision'];
                    }
                    
                    if (!empty($data['countryName'])) {
                        $addressParts[] = $data['countryName'];
                    }
                    
                    if (!empty($addressParts)) {
                        return implode(', ', $addressParts);
                    }
                }
            }

            // Final fallback: Return coordinates as address
            return "GPS Location: {$latitude}, {$longitude}";

        } catch (\Exception $e) {
            Log::warning('BigDataCloud geocoding failed', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage()
            ]);

            // Final fallback: Return coordinates as address
            return "GPS Location: {$latitude}, {$longitude}";
        }
    }

    /**
     * Get detailed address information from coordinates
     */
    public static function getDetailedAddress(float $latitude, float $longitude): array
    {
        try {
            $response = Http::timeout(10)->get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'json',
                'lat' => $latitude,
                'lon' => $longitude,
                'zoom' => 18,
                'addressdetails' => 1,
                'accept-language' => 'en'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['address'])) {
                    $address = $data['address'];
                    
                    return [
                        'full_address' => $data['display_name'] ?? null,
                        'street' => $address['road'] ?? $address['pedestrian'] ?? null,
                        'house_number' => $address['house_number'] ?? null,
                        'suburb' => $address['suburb'] ?? $address['neighbourhood'] ?? null,
                        'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? null,
                        'state' => $address['state'] ?? $address['province'] ?? null,
                        'country' => $address['country'] ?? null,
                        'postcode' => $address['postcode'] ?? null,
                        'coordinates' => [
                            'latitude' => $latitude,
                            'longitude' => $longitude
                        ]
                    ];
                }
            }

            return [
                'full_address' => "GPS Location: {$latitude}, {$longitude}",
                'coordinates' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]
            ];

        } catch (\Exception $e) {
            Log::warning('Detailed geocoding failed', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage()
            ]);

            return [
                'full_address' => "GPS Location: {$latitude}, {$longitude}",
                'coordinates' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]
            ];
        }
    }
}
