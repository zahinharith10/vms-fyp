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
