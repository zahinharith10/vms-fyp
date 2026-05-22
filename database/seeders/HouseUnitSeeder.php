<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HouseUnit;

class HouseUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. APARTMENTS
        // Blocks: 44, 46, 48, 56, 58, 60 (30 units each -> 6 units per floor, 5 floors)
        $fullApartmentBlocks = ['44', '46', '48', '56', '58', '60'];
        $floors = ['1', '2', '3', '4', '5'];

        foreach ($fullApartmentBlocks as $block) {
            foreach ($floors as $floor) {
                // 6 units per floor
                for ($i = 1; $i <= 6; $i++) {
                    $unitNumber = str_pad($i, 2, '0', STR_PAD_LEFT);
                    HouseUnit::updateOrCreate([
                        'block' => $block,
                        'floor' => $floor,
                        'unit_number' => $unitNumber,
                    ]);
                }
            }
        }

        // Block 62: (20 units -> 4 units per floor, 5 floors)
        $smallApartmentBlock = '62';
        foreach ($floors as $floor) {
            // 4 units per floor
            for ($i = 1; $i <= 4; $i++) {
                $unitNumber = str_pad($i, 2, '0', STR_PAD_LEFT);
                HouseUnit::updateOrCreate([
                    'block' => $smallApartmentBlock,
                    'floor' => $floor,
                    'unit_number' => $unitNumber,
                ]);
            }
        }


        // 2. TOWNHOUSES
        // Blocks: 50, 52, 54 (14 units each block -> 7 on Ground, 7 on Floor 2)
        $townhouseBlocks = ['50', '52', '54'];
        $townhouseFloors = ['G', '2'];

        foreach ($townhouseBlocks as $block) {
            foreach ($townhouseFloors as $floor) {
                // 7 units per entrance floor
                for ($i = 1; $i <= 7; $i++) {
                    $unitNumber = str_pad($i, 2, '0', STR_PAD_LEFT);
                    HouseUnit::updateOrCreate([
                        'block' => $block,
                        'floor' => $floor,
                        'unit_number' => $unitNumber,
                    ]);
                }
            }
        }
    }
}
