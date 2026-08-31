<?php

use App\Models\SecurityFinding;

it('searches findings using columns from the security findings table', function () {
    SecurityFinding::query()->create([
        'finding_type' => 'PUBLIC_DATABASE_ACCOUNT',
        'category' => 'ACCESS_CONTROL',
        'severity' => 'HIGH',
        'title' => 'Public database account',
        'object_name' => 'production_users',
        'status' => 'OPEN',
    ]);

    $this->get(route('security-findings.index', ['search' => 'PUBLIC_DATABASE_ACCOUNT']))
        ->assertOk()
        ->assertSee('Public database account');

    $this->get(route('security-findings.index', ['search' => 'production_users']))
        ->assertOk()
        ->assertSee('Public database account');
});

it('loads and displays finding history without swallowing relationship errors', function () {
    $finding = SecurityFinding::query()->create([
        'finding_type' => 'WEAK_PRIVILEGE',
        'category' => 'PRIVILEGE',
        'severity' => 'MEDIUM',
        'title' => 'Weak database privilege',
        'status' => 'OPEN',
    ]);

    $this->post(route('security-findings.resolve', $finding))->assertRedirect();

    $this->get(route('security-findings.show', $finding))
        ->assertOk()
        ->assertSee('Finding History')
        ->assertSee('RESOLVE')
        ->assertSee('WEAK_PRIVILEGE');
});
