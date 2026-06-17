<?php

use App\Models\Guard;
use App\Models\Visitor;
use App\Models\Visit;
use App\Models\DeliveryPersonnel;
use App\Models\DeliveryLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('approved visit or delivery approved less than 24 hours ago is not expired', function () {
    $visitor = Visitor::create([
        'name' => 'John Doe',
        'phone' => '0123456789',
        'ic_number' => '950101-14-1234',
        'vehicle_number' => 'VMS1234',
    ]);

    $visit = Visit::create([
        'visitor_id' => $visitor->id,
        'unit_number' => '1-1-1',
        'purpose' => 'Visiting friends',
        'status' => 'Approved',
        'approved_at' => now()->subHours(12),
        'qr_code_token' => 'valid_token_123',
    ]);

    expect($visit->is_expired)->toBeFalse();
    expect($visit->status)->toBe('Approved');
});

test('approved visit or delivery approved more than 24 hours ago is expired dynamically', function () {
    $visitor = Visitor::create([
        'name' => 'John Doe',
        'phone' => '0123456789',
        'ic_number' => '950101-14-1234',
        'vehicle_number' => 'VMS1234',
    ]);

    $visit = Visit::create([
        'visitor_id' => $visitor->id,
        'unit_number' => '1-1-1',
        'purpose' => 'Visiting friends',
        'status' => 'Approved',
        'approved_at' => now()->subHours(25),
        'qr_code_token' => 'expired_token_123',
    ]);

    expect($visit->is_expired)->toBeTrue();
    expect($visit->status)->toBe('Expired');
});

test('active scope correctly filters out expired visits and deliveries', function () {
    $visitor = Visitor::create([
        'name' => 'John Doe',
        'phone' => '0123456789',
        'ic_number' => '950101-14-1234',
        'vehicle_number' => 'VMS1234',
    ]);

    // Active (Approved <= 24 hours)
    $activeVisit = Visit::create([
        'visitor_id' => $visitor->id,
        'unit_number' => '1-1-1',
        'purpose' => 'Visiting friends',
        'status' => 'Approved',
        'approved_at' => now()->subHours(10),
        'qr_code_token' => 'active_token',
    ]);

    // Expired (Approved > 24 hours)
    $expiredVisit = Visit::create([
        'visitor_id' => $visitor->id,
        'unit_number' => '1-1-1',
        'purpose' => 'Visiting friends',
        'status' => 'Approved',
        'approved_at' => now()->subHours(25),
        'qr_code_token' => 'expired_token',
    ]);

    // Pending (Should not expire regardless of age)
    $pendingVisit = Visit::create([
        'visitor_id' => $visitor->id,
        'unit_number' => '1-1-1',
        'purpose' => 'Visiting friends',
        'status' => 'Pending',
        'created_at' => now()->subHours(30),
        'qr_code_token' => 'pending_token',
    ]);

    $activeVisits = Visit::active()->get();

    expect($activeVisits->contains($activeVisit->id))->toBeTrue();
    expect($activeVisits->contains($pendingVisit->id))->toBeTrue();
    expect($activeVisits->contains($expiredVisit->id))->toBeFalse();
});

test('guard scan lookup of an expired visit fails', function () {
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
        'name' => 'John Doe',
        'phone' => '0123456789',
        'ic_number' => '950101-14-1234',
        'vehicle_number' => 'VMS1234',
    ]);

    $expiredVisit = Visit::create([
        'visitor_id' => $visitor->id,
        'unit_number' => '1-1-1',
        'purpose' => 'Visiting friends',
        'status' => 'Approved',
        'approved_at' => now()->subHours(25),
        'qr_code_token' => 'expired_token',
    ]);

    $response = $this->actingAs($guard, 'guard')
        ->postJson(route('guard.scan.lookup'), [
            'token' => 'expired_token:final',
        ]);

    $response->assertStatus(410);
    $response->assertJson([
        'success' => false,
        'message' => 'This QR code/pass has expired. Passes are only valid for 24 hours after approval.',
    ]);
});

test('guard check-in of an expired visit fails', function () {
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
        'name' => 'John Doe',
        'phone' => '0123456789',
        'ic_number' => '950101-14-1234',
        'vehicle_number' => 'VMS1234',
    ]);

    $expiredVisit = Visit::create([
        'visitor_id' => $visitor->id,
        'unit_number' => '1-1-1',
        'purpose' => 'Visiting friends',
        'status' => 'Approved',
        'approved_at' => now()->subHours(25),
        'qr_code_token' => 'expired_token',
    ]);

    $response = $this->actingAs($guard, 'guard')
        ->postJson(route('guard.scan.checkin'), [
            'visit_id' => $expiredVisit->id,
        ]);

    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
        'message' => 'This guest pass QR code has expired.',
    ]);
});
