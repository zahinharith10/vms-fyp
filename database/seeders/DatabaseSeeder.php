<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        \App\Models\User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        $this->call([
            HouseUnitSeeder::class,
        ]);

        // Seed Admin Account
        \App\Models\Admin::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Sri Ayu Admin',
                'password' => bcrypt('password'),
                'status' => 'Active',
            ]
        );

        // Seed Guard Account
        \App\Models\Guard::updateOrCreate(
            ['email' => 'guard@guard.com'],
            [
                'name' => 'Security Guard Harith',
                'employee_id' => 'EMP-0001',
                'ic_number' => '990101147777',
                'phone' => '0123456789',
                'shift' => 'Morning',
                'password' => bcrypt('password'),
                'status' => 'Active',
            ]
        );

        // Get first house unit for Resident
        $unit = \App\Models\HouseUnit::first();

        // Seed Resident Account
        if ($unit) {
            \App\Models\Resident::updateOrCreate(
                ['email' => 'resident@resident.com'],
                [
                    'house_unit_id' => $unit->id,
                    'name' => 'Zahin Harith',
                    'phone' => '0123456789',
                    'password' => bcrypt('password'),
                    'ic_number' => '990101145678',
                    'type' => 'owner',
                    'status' => 'Active',
                ]
            );
        }

        // Seed Delivery Personnel
        \App\Models\DeliveryPersonnel::updateOrCreate(
            ['email' => 'driver@driver.com'],
            [
                'name' => 'GrabFood Rider',
                'company' => 'GrabFood',
                'phone' => '01987654321',
                'vehicle_type' => 'Motorcycle',
                'vehicle_number' => 'abc123',
                'ic_number' => '990101149999',
                'face_descriptor' => json_encode([]),
                'status' => 'Active',
            ]
        );
    }
}
