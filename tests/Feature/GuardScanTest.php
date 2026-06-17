<?php

use App\Models\Guard;
use App\Models\Visitor;
use App\Models\Visit;
use App\Models\DeliveryPersonnel;
use App\Models\DeliveryLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guard can lookup visit and auto checkout stale visits', function () {
    // Create a guard
    $guard = Guard::create([
        'name' => 'Guard John',
        'employee_id' => 'G123',
        'ic_number' => '900101-14-1234',
        'phone' => '0123456789',
        'shift' => 'Morning',
        'email' => 'john@sriayu.com',
        'password' => bcrypt('password'),
    ]);

    // Create a visitor
    $visitor = Visitor::create([
        'name' => 'Guest Visitor',
        'phone' => '0112233445',
        'ic_number' => '950505-10-5678',
        'vehicle_number' => 'BCA1234',
    ]);

    // Create a stale visit (older than 24 hours) that should be auto checked out
    $staleVisit = Visit::create([
        'visitor_id' => $visitor->id,
        'unit_number' => '44-1-01',
        'purpose' => 'Meeting',
        'status' => 'Checked In',
        'check_in_time' => now()->subHours(25),
        'qr_code_token' => 'TOKEN_STALE_123',
    ]);

    // Create an active visit that should remain active
    $activeVisit = Visit::create([
        'visitor_id' => $visitor->id,
        'unit_number' => '44-1-02',
        'purpose' => 'Delivery',
        'status' => 'Checked In',
        'check_in_time' => now()->subHours(2),
        'qr_code_token' => 'TOKEN_ACTIVE_123',
    ]);

    // Request lookup
    $response = $this->actingAs($guard, 'guard')
        ->postJson(route('guard.scan.lookup'), [
            'token' => 'TOKEN_ACTIVE_123:final',
        ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'is_delivery' => false,
        'visit' => [
            'id' => $activeVisit->id,
            'visitor_name' => 'Guest Visitor',
            'status' => 'Checked In',
        ],
    ]);

    // Verify the stale visit was auto checked out
    $staleVisit->refresh();
    expect($staleVisit->status)->toBe('Checked Out');
    expect($staleVisit->check_out_time)->not->toBeNull();

    // Verify the active visit remained checked in
    $activeVisit->refresh();
    expect($activeVisit->status)->toBe('Checked In');
});

test('guard can check in visitor successfully and notify resident', function () {
    // Create a guard
    $guard = Guard::create([
        'name' => 'Guard John',
        'employee_id' => 'G123',
        'ic_number' => '900101-14-1234',
        'phone' => '0123456789',
        'shift' => 'Morning',
        'email' => 'john@sriayu.com',
        'password' => bcrypt('password'),
    ]);

    // Create a HouseUnit
    $houseUnit = \App\Models\HouseUnit::create([
        'block' => '44',
        'floor' => '1',
        'unit_number' => '01',
    ]);

    // Create a Resident
    $resident = \App\Models\Resident::create([
        'name' => 'Resident Jane',
        'email' => 'jane@example.com',
        'phone' => '0198765432',
        'password' => bcrypt('password'),
        'house_unit_id' => $houseUnit->id,
        'status' => 'active',
    ]);

    // Create a visitor
    $visitor = Visitor::create([
        'name' => 'Guest Visitor',
        'phone' => '0112233445',
        'ic_number' => '950505-10-5678',
        'vehicle_number' => 'BCA1234',
    ]);

    // Create an Approved visit
    $visit = Visit::create([
        'visitor_id' => $visitor->id,
        'unit_number' => '44-1-01',
        'purpose' => 'Social',
        'status' => 'Approved',
        'qr_code_token' => 'TOKEN_APPROVED_123',
    ]);

    // Request checkin
    $response = $this->actingAs($guard, 'guard')
        ->postJson(route('guard.scan.checkin'), [
            'visit_id' => $visit->id,
        ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Visitor checked in successfully!',
    ]);

    $visit->refresh();
    expect($visit->status)->toBe('Checked In');
    expect($visit->check_in_time)->not->toBeNull();
});

test('guard can check in visitor with vehicle and auto-assign parking lot', function () {
    $guard = Guard::create([
        'name' => 'Guard John',
        'employee_id' => 'G123',
        'ic_number' => '900101-14-1234',
        'phone' => '0123456789',
        'shift' => 'Morning',
        'email' => 'john@sriayu.com',
        'password' => bcrypt('password'),
    ]);

    $visitor = Visitor::create([
        'name' => 'Visitor With Car',
        'phone' => '0112233446',
        'ic_number' => '950505-10-5679',
        'vehicle_number' => 'WQA1234',
    ]);

    $visit = Visit::create([
        'visitor_id' => $visitor->id,
        'unit_number' => '44-1-01',
        'purpose' => 'Social',
        'status' => 'Approved',
        'qr_code_token' => 'TOKEN_VEHICLE_123',
    ]);

    $response = $this->actingAs($guard, 'guard')
        ->postJson(route('guard.scan.checkin'), [
            'visit_id' => $visit->id,
        ]);

    $response->assertStatus(200);
    $visit->refresh();
    expect($visit->parking_lot_number)->toBe(1);
});

test('guard cannot check in visitor with vehicle when all 15 parking slots are full', function () {
    $guard = Guard::create([
        'name' => 'Guard John',
        'employee_id' => 'G123',
        'ic_number' => '900101-14-1234',
        'phone' => '0123456789',
        'shift' => 'Morning',
        'email' => 'john@sriayu.com',
        'password' => bcrypt('password'),
    ]);

    // Create 15 already checked in visits with parking lot numbers
    for ($i = 1; $i <= 15; $i++) {
        $visitor = Visitor::create([
            'name' => 'Visitor ' . $i,
            'phone' => '01122334' . str_pad($i, 2, '0', STR_PAD_LEFT),
            'ic_number' => '950505-10-56' . str_pad($i, 2, '0', STR_PAD_LEFT),
            'vehicle_number' => 'PLATE' . $i,
        ]);

        Visit::create([
            'visitor_id' => $visitor->id,
            'unit_number' => '44-1-01',
            'purpose' => 'Social',
            'status' => 'Checked In',
            'check_in_time' => now(),
            'parking_lot_number' => $i,
            'qr_code_token' => 'TOKEN_OCCUPIED_' . $i,
        ]);
    }

    // Now attempt to check in the 16th visitor who has a vehicle
    $visitor16 = Visitor::create([
        'name' => 'Visitor 16',
        'phone' => '0112233499',
        'ic_number' => '950505-10-9999',
        'vehicle_number' => 'PLATE16',
    ]);

    $visit16 = Visit::create([
        'visitor_id' => $visitor16->id,
        'unit_number' => '44-1-01',
        'purpose' => 'Social',
        'status' => 'Approved',
        'qr_code_token' => 'TOKEN_16',
    ]);

    $response = $this->actingAs($guard, 'guard')
        ->postJson(route('guard.scan.checkin'), [
            'visit_id' => $visit16->id,
        ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'Visitor parking is full! All 15 visitor parking slots are occupied.',
    ]);
});

test('guard can check in visitor with vehicle as drop off/park outside even when parking is full', function () {
    $guard = Guard::create([
        'name' => 'Guard John',
        'employee_id' => 'G123',
        'ic_number' => '900101-14-1234',
        'phone' => '0123456789',
        'shift' => 'Morning',
        'email' => 'john@sriayu.com',
        'password' => bcrypt('password'),
    ]);

    // Create 15 already checked in visits with parking lot numbers
    for ($i = 1; $i <= 15; $i++) {
        $visitor = Visitor::create([
            'name' => 'Visitor ' . $i,
            'phone' => '01122334' . str_pad($i, 2, '0', STR_PAD_LEFT),
            'ic_number' => '950505-10-56' . str_pad($i, 2, '0', STR_PAD_LEFT),
            'vehicle_number' => 'PLATE' . $i,
        ]);

        Visit::create([
            'visitor_id' => $visitor->id,
            'unit_number' => '44-1-01',
            'purpose' => 'Social',
            'status' => 'Checked In',
            'check_in_time' => now(),
            'parking_lot_number' => $i,
            'qr_code_token' => 'TOKEN_OCCUPIED_' . $i,
        ]);
    }

    // Create a 16th visitor with a vehicle who wants to park outside
    $visitor16 = Visitor::create([
        'name' => 'Visitor 16',
        'phone' => '0112233499',
        'ic_number' => '950505-10-9999',
        'vehicle_number' => 'PLATE16',
    ]);

    $visit16 = Visit::create([
        'visitor_id' => $visitor16->id,
        'unit_number' => '44-1-01',
        'purpose' => 'Social',
        'status' => 'Approved',
        'qr_code_token' => 'TOKEN_16',
    ]);

    // Send checkin request with park_outside = true
    $response = $this->actingAs($guard, 'guard')
        ->postJson(route('guard.scan.checkin'), [
            'visit_id' => $visit16->id,
            'park_outside' => true,
        ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Visitor checked in successfully!',
    ]);

    $visit16->refresh();
    expect($visit16->status)->toBe('Checked In');
    expect($visit16->parking_lot_number)->toBeNull();
});


