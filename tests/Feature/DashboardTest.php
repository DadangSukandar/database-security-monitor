<?php

use App\Models\DatabaseConnection;
use App\Models\SecurityFinding;
use App\Models\User;

test('dashboard can be accessed by guests', function () {
    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertViewIs('dashboard');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertViewIs('dashboard');
});

test('dashboard contains connection statistics', function () {
    DatabaseConnection::create([
        'name' => 'Active Database',
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'test_active',
        'username' => 'root',
        'password' => null,
        'is_active' => true,
    ]);

    DatabaseConnection::create([
        'name' => 'Inactive Database',
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'test_inactive',
        'username' => 'root',
        'password' => null,
        'is_active' => false,
    ]);

    $response = $this->get(route('dashboard'));

    $response->assertOk();

    $response->assertViewHas('totalConnections', 2);
    $response->assertViewHas('activeConnections', 1);
});

test('dashboard contains security finding statistics', function () {
    SecurityFinding::create([
        'finding_type' => 'TEST_CRITICAL',
        'category' => 'SECURITY',
        'severity' => 'CRITICAL',
        'title' => 'Critical Test Finding',
        'description' => 'Critical finding for dashboard testing.',
        'status' => 'OPEN',
        'detected_at' => now(),
    ]);

    SecurityFinding::create([
        'finding_type' => 'TEST_HIGH',
        'category' => 'SECURITY',
        'severity' => 'HIGH',
        'title' => 'High Test Finding',
        'description' => 'High finding for dashboard testing.',
        'status' => 'OPEN',
        'detected_at' => now(),
    ]);

    SecurityFinding::create([
        'finding_type' => 'TEST_MEDIUM',
        'category' => 'SECURITY',
        'severity' => 'MEDIUM',
        'title' => 'Medium Test Finding',
        'description' => 'Medium finding for dashboard testing.',
        'status' => 'OPEN',
        'detected_at' => now(),
    ]);

    SecurityFinding::create([
        'finding_type' => 'TEST_LOW',
        'category' => 'SECURITY',
        'severity' => 'LOW',
        'title' => 'Low Test Finding',
        'description' => 'Low finding for dashboard testing.',
        'status' => 'OPEN',
        'detected_at' => now(),
    ]);

    SecurityFinding::create([
        'finding_type' => 'TEST_RESOLVED',
        'category' => 'SECURITY',
        'severity' => 'CRITICAL',
        'title' => 'Resolved Test Finding',
        'description' => 'Resolved finding must not count as open.',
        'status' => 'RESOLVED',
        'detected_at' => now(),
        'resolved_at' => now(),
    ]);

    $response = $this->get(route('dashboard'));

    $response->assertOk();

    $response->assertViewHas('criticalFindings', 1);
    $response->assertViewHas('highFindings', 1);
    $response->assertViewHas('mediumFindings', 1);
    $response->assertViewHas('lowFindings', 1);
    $response->assertViewHas('totalFindings', 4);
});

test('dashboard contains recent open security findings', function () {
    for ($i = 1; $i <= 3; $i++) {
        SecurityFinding::create([
            'finding_type' => 'TEST_OPEN_'.$i,
            'category' => 'SECURITY',
            'severity' => 'HIGH',
            'title' => 'Open Test Finding '.$i,
            'description' => 'Open finding for dashboard test.',
            'status' => 'OPEN',
            'detected_at' => now()->subMinutes($i),
        ]);
    }

    SecurityFinding::create([
        'finding_type' => 'TEST_RESOLVED',
        'category' => 'SECURITY',
        'severity' => 'HIGH',
        'title' => 'Resolved Finding',
        'description' => 'Should not appear in recent open findings.',
        'status' => 'RESOLVED',
        'detected_at' => now(),
        'resolved_at' => now(),
    ]);

    $response = $this->get(route('dashboard'));

    $response->assertOk();

    $response->assertViewHas(
        'recentSecurityFindings',
        function ($findings) {
            return $findings->count() === 3
                && $findings->every(
                    fn ($finding) => $finding->status === 'OPEN'
                );
        }
    );
});

test('dashboard provides all required blade variables', function () {
    $response = $this->get(route('dashboard'));

    $response->assertOk();

    $response->assertViewHasAll([
        'totalConnections',
        'activeConnections',
        'securityScore',
        'recentSecurityFindings',
        'recentFindings',
        'criticalFindings',
        'highFindings',
        'mediumFindings',
        'lowFindings',
        'totalFindings',
        'databaseConnections',
    ]);
});