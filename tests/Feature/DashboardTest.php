<?php

use App\Models\DatabaseConnection;
use App\Models\SecurityFinding;
use App\Models\Team;
use App\Models\User;

function dashboardTestActor(): array
{
    $user =
        User::factory()->create();

    $team =
        Team::factory()->create();

    $team->members()->attach(
        $user->id,
        [
            'role' => 'admin',
        ]
    );

    expect(
        $user->switchTeam($team)
    )->toBeTrue();

    return [
        $user,
        $team,
    ];
}

function dashboardTestConnection(
    Team $team,
    array $attributes = []
): DatabaseConnection {
    $connection =
        new DatabaseConnection(
            array_merge([
                'name' => 'Dashboard Database',

                'driver' => 'mysql',

                'host' => '127.0.0.1',

                'port' => 3306,

                'database' => 'dashboard_test',

                'username' => 'root',

                'password' => null,

                'is_active' => true,
            ], $attributes)
        );

    $connection->team_id =
        $team->id;

    $connection->save();

    return $connection;
}

function dashboardTestFinding(
    Team $team,
    array $attributes = []
): SecurityFinding {
    $finding =
        new SecurityFinding(
            array_merge([
                'finding_type' => 'TEST_FINDING',

                'category' => 'SECURITY',

                'severity' => 'HIGH',

                'title' => 'Dashboard Test Finding',

                'description' => 'Security finding for dashboard testing.',

                'status' => 'OPEN',

                'detected_at' => now(),
            ], $attributes)
        );

    $finding->team_id =
        $team->id;

    $finding->save();

    return $finding;
}

test('dashboard cannot be accessed by guests', function () {
    $this
        ->get(route('dashboard'))
        ->assertRedirect();
});

test('authenticated users can visit the dashboard', function () {
    [$user] =
        dashboardTestActor();

    $response =
        $this
            ->actingAs($user)
            ->get(route('dashboard'));

    $response->assertOk();

    $response->assertViewIs(
        'dashboard'
    );
});

test('dashboard contains connection statistics', function () {
    [$user, $team] =
        dashboardTestActor();

    dashboardTestConnection(
        $team,
        [
            'name' => 'Active Database',

            'database' => 'test_active',

            'is_active' => true,
        ]
    );

    dashboardTestConnection(
        $team,
        [
            'name' => 'Inactive Database',

            'database' => 'test_inactive',

            'is_active' => false,
        ]
    );

    $response =
        $this
            ->actingAs($user)
            ->get(route('dashboard'));

    $response->assertOk();

    $response->assertViewHas(
        'totalConnections',
        2
    );

    $response->assertViewHas(
        'activeConnections',
        1
    );
});

test('dashboard contains security finding statistics', function () {
    [$user, $team] =
        dashboardTestActor();

    dashboardTestFinding(
        $team,
        [
            'finding_type' => 'TEST_CRITICAL',

            'severity' => 'CRITICAL',

            'title' => 'Critical Test Finding',

            'description' => 'Critical finding for dashboard testing.',
        ]
    );

    dashboardTestFinding(
        $team,
        [
            'finding_type' => 'TEST_HIGH',

            'severity' => 'HIGH',

            'title' => 'High Test Finding',

            'description' => 'High finding for dashboard testing.',
        ]
    );

    dashboardTestFinding(
        $team,
        [
            'finding_type' => 'TEST_MEDIUM',

            'severity' => 'MEDIUM',

            'title' => 'Medium Test Finding',

            'description' => 'Medium finding for dashboard testing.',
        ]
    );

    dashboardTestFinding(
        $team,
        [
            'finding_type' => 'TEST_LOW',

            'severity' => 'LOW',

            'title' => 'Low Test Finding',

            'description' => 'Low finding for dashboard testing.',
        ]
    );

    dashboardTestFinding(
        $team,
        [
            'finding_type' => 'TEST_RESOLVED',

            'severity' => 'CRITICAL',

            'title' => 'Resolved Test Finding',

            'description' => 'Resolved finding must not count as open.',

            'status' => 'RESOLVED',

            'resolved_at' => now(),
        ]
    );

    $response =
        $this
            ->actingAs($user)
            ->get(route('dashboard'));

    $response->assertOk();

    $response->assertViewHas(
        'criticalFindings',
        1
    );

    $response->assertViewHas(
        'highFindings',
        1
    );

    $response->assertViewHas(
        'mediumFindings',
        1
    );

    $response->assertViewHas(
        'lowFindings',
        1
    );

    $response->assertViewHas(
        'totalFindings',
        4
    );
});

test('dashboard contains recent open security findings', function () {
    [$user, $team] =
        dashboardTestActor();

    for ($i = 1; $i <= 3; $i++) {
        dashboardTestFinding(
            $team,
            [
                'finding_type' => 'TEST_OPEN_'.$i,

                'severity' => 'HIGH',

                'title' => 'Open Test Finding '.$i,

                'description' => 'Open finding for dashboard test.',

                'detected_at' => now()->subMinutes($i),
            ]
        );
    }

    dashboardTestFinding(
        $team,
        [
            'finding_type' => 'TEST_RESOLVED',

            'severity' => 'HIGH',

            'title' => 'Resolved Finding',

            'description' => 'Should not appear in recent open findings.',

            'status' => 'RESOLVED',

            'resolved_at' => now(),
        ]
    );

    $response =
        $this
            ->actingAs($user)
            ->get(route('dashboard'));

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
    [$user] =
        dashboardTestActor();

    $response =
        $this
            ->actingAs($user)
            ->get(route('dashboard'));

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
