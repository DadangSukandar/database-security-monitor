<?php

use App\Models\User;

it('renders the shared dashboard navigation across application pages', function (
    string $routeName,
    bool $requiresAuthentication
) {
    if ($requiresAuthentication) {
        $this->actingAs(
            User::factory()->create()
        );
    }

    $this->get(route($routeName))
        ->assertOk()
        ->assertSeeInOrder([
            'Guardium Center',
            'Dashboard',
            'Security Overview',
            'Database Connections',
            'Database Discovery',
            'SQL Query Console',
            'Activity Monitoring',
            'Security Alerts',
            'Security Audit',
            'Security Findings',
            'Security Reports',
        ]);
})->with([
    'dashboard' => [
        'dashboard',
        false,
    ],
    'shared blade layout' => [
        'security-alerts.index',
        true,
    ],
    'activity standalone page' => [
        'database-activities.index',
        true,
    ],
    'SQL console standalone page' => [
        'sql-query.index',
        true,
    ],
]);
