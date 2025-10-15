<?php

namespace Database\Seeders;

use App\Models\AffectedEvent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AffectedEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $affectedEvents = [
            'California Wildfires',
            'Kentucky Floods',
            'Hurricane Fiona',
            'Inflation',
            'Covid-19',
            'Unemployment',
            'None',
        ];

        foreach ($affectedEvents as $eventName) {
            AffectedEvent::firstOrCreate(
                ['name' => $eventName],
                [
                    'name' => $eventName,
                    'is_active' => true,
                ]
            );
        }
    }
}
