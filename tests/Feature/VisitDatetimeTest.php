<?php

use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('casts visit check-in time as carbon in malaysia timezone', function () {
    $visitor = Visitor::create([
        'name' => 'Test Visitor',
        'phone' => '0111111111',
        'ic_number' => '900101-14-1111',
        'vehicle_number' => 'ABC1234',
    ]);

    $checkIn = Carbon::parse('2026-05-23 15:25:29', config('app.timezone'));

    $visit = Visit::create([
        'visitor_id' => $visitor->id,
        'unit_number' => '44-1-01',
        'purpose' => 'Meeting',
        'status' => 'Checked In',
        'check_in_time' => $checkIn,
        'qr_code_token' => 'TEST_TOKEN',
    ]);

    $visit->refresh();

    expect($visit->check_in_time)->toBeInstanceOf(Carbon::class);
    expect($visit->check_in_time->timezone->getName())->toBe('Asia/Kuala_Lumpur');
    expect($visit->check_in_time->format('Y-m-d H:i:s'))->toBe('2026-05-23 15:25:29');

    $json = $visit->check_in_time->toJSON();
    expect($json)->toContain('T');
    expect($json)->toMatch('/Z|\+08:00/');
});
