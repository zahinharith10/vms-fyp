<?php

use App\Models\DeliveryLog;
use App\Models\DeliveryPersonnel;
use App\Models\DeliveryRun;
use App\Models\HouseUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    HouseUnit::create(['block' => '44', 'floor' => '1', 'unit_number' => '01']);
    HouseUnit::create(['block' => '44', 'floor' => '1', 'unit_number' => '02']);
    HouseUnit::create(['block' => '44', 'floor' => '2', 'unit_number' => '03']);
});

function createDeliveryPersonnel(): DeliveryPersonnel
{
    return DeliveryPersonnel::create([
        'name' => 'Courier Ali',
        'email' => 'ali@courier.test',
        'company' => 'Fast Parcel',
        'vehicle_type' => 'Motorcycle',
        'vehicle_number' => 'ABC1234',
        'phone' => '0123456789',
        'ic_number' => '900101-14-9999',
        'face_descriptor' => json_encode([]),
        'status' => 'Active',
    ]);
}

it('creates a single delivery trip with one log', function () {
    $personnel = createDeliveryPersonnel();

    $response = $this->actingAs($personnel, 'delivery')->post(route('delivery.trips.store'), [
        'delivery_type' => 'single',
        'unit_number' => '44 - 1 - 01',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $run = DeliveryRun::first();
    expect($run)->not->toBeNull();
    expect($run->type)->toBe('single');
    expect($run->logs)->toHaveCount(1);
    expect($run->logs->first()->destination)->toBe('44 - 1 - 01');
});

it('creates a multi-stop delivery trip with multiple logs', function () {
    $personnel = createDeliveryPersonnel();

    $response = $this->actingAs($personnel, 'delivery')->post(route('delivery.trips.store'), [
        'delivery_type' => 'multi',
        'unit_numbers' => [
            '44 - 1 - 01',
            '44 - 1 - 02',
            '44 - 2 - 03',
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $run = DeliveryRun::first();
    expect($run->type)->toBe('multi');
    expect($run->logs)->toHaveCount(3);
});

it('rejects multi-stop delivery with fewer than two units', function () {
    $personnel = createDeliveryPersonnel();

    $response = $this->actingAs($personnel, 'delivery')->post(route('delivery.trips.store'), [
        'delivery_type' => 'multi',
        'unit_numbers' => ['44 - 1 - 01'],
    ]);

    $response->assertSessionHasErrors('unit_numbers');
    expect(DeliveryRun::count())->toBe(0);
});

it('prevents creating a new trip while an active trip is open', function () {
    $personnel = createDeliveryPersonnel();

    DeliveryLog::create([
        'delivery_personnel_id' => $personnel->id,
        'delivery_run_id' => DeliveryRun::create([
            'delivery_personnel_id' => $personnel->id,
            'type' => 'single',
            'status' => 'Approved',
        ])->id,
        'destination' => '44 - 1 - 01',
        'status' => 'Approved',
    ]);

    $response = $this->actingAs($personnel, 'delivery')->post(route('delivery.trips.store'), [
        'delivery_type' => 'single',
        'unit_number' => '44 - 1 - 02',
    ]);

    $response->assertSessionHas('error');
    expect(DeliveryRun::count())->toBe(1);
});
