<?php

it('renders the shared dashboard navigation across application pages', function (string $routeName) {
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
    'dashboard' => 'dashboard',
    'shared blade layout' => 'security-alerts.index',
    'activity standalone page' => 'database-activities.index',
    'SQL console standalone page' => 'sql-query.index',
]);
