<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new visitors can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test Visitor',
        'email' => 'visitor@example.com',
        'phone' => '0112345678',
        'ic_number' => '950101-14-1234',
        'vehicle_number' => 'W123A',
        'face_descriptor' => ['dummy' => 'descriptor'],
    ]);

    $this->assertAuthenticated('visitor');
    $response->assertRedirect(route('visitor.dashboard'));
});

test('registration fails with invalid phone formats', function ($phone) {
    $response = $this->post('/register', [
        'name' => 'Test Visitor',
        'email' => 'visitor.invalid@example.com',
        'phone' => $phone,
        'ic_number' => '950101-14-1234',
        'vehicle_number' => 'W123A',
        'face_descriptor' => ['dummy' => 'descriptor'],
    ]);

    $response->assertSessionHasErrors(['phone']);
})->with([
    '01234',            // Too short
    '0234567890',       // Invalid prefix (02 is not valid Malaysian mobile)
    'abcdefghij',       // Non-numeric
    '011123456789012',  // Too long
]);

test('registration passes with various valid Malaysian phone formats', function ($phone) {
    $response = $this->post('/register', [
        'name' => 'Test Visitor ' . rand(1, 100),
        'email' => 'visitor.' . rand(1, 10000) . '@example.com',
        'phone' => $phone,
        'ic_number' => '950101-14-1234',
        'vehicle_number' => 'W123A',
        'face_descriptor' => ['dummy' => 'descriptor'],
    ]);

    $response->assertRedirect(route('visitor.dashboard'));
})->with([
    '012-3456789',
    '011-23456789',
    '+60123456789',
    '6012-3456789',
    '012 345 6789',
]);
