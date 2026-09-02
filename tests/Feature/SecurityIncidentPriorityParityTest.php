<?php

use App\Models\SecurityAlert;
use App\Models\SecurityIncident;

function createPriorityParityIncident(array $attributes): SecurityIncident
{
    $alert = SecurityAlert::query()->create([
        'alert_type' => 'VULNERABILITY',
        'severity' => $attributes['severity'] ?? 'LOW',
        'title' => 'Priority parity source alert '.fake()->uuid(),
        'status' => 'OPEN',
        'detected_at' => now(),
    ]);

    return SecurityIncident::query()->create(array_merge([
        'incident_number' => 'INC-PARITY-'.fake()->unique()->numerify('########'),
        'security_alert_id' => $alert->id,
        'title' => 'Priority parity incident',
        'severity' => 'LOW',
        'status' => 'OPEN',
        'opened_at' => now(),
    ], $attributes));
}

it('keeps late acknowledgement priority consistent between php and database scopes', function () {
    $openedAt = now()->subDays(3);

    $lateAcknowledged = createPriorityParityIncident([
        'severity' => 'LOW',
        'status' => 'ACKNOWLEDGED',
        'opened_at' => $openedAt,
        'acknowledged_at' => $openedAt->copy()->addMinutes(1441),
    ]);

    $normalHigh = createPriorityParityIncident([
        'severity' => 'HIGH',
        'opened_at' => now()->subMinutes(10),
    ]);

    expect($lateAcknowledged->triagePriority())->toBe('P1');

    $p1Ids = SecurityIncident::query()
        ->whereTriagePriority('P1')
        ->pluck('id');

    expect($p1Ids)->toContain($lateAcknowledged->id);

    $orderedIds = SecurityIncident::query()
        ->orderByTriagePriority()
        ->pluck('id');

    expect($orderedIds->search($lateAcknowledged->id))
        ->toBeLessThan($orderedIds->search($normalHigh->id));
});

it('uses low sla fallback for unknown severity in database scopes', function () {
    $breachedUnknown = createPriorityParityIncident([
        'severity' => 'INFO',
        'opened_at' => now()->subMinutes(1441),
    ]);

    $dueSoonUnknown = createPriorityParityIncident([
        'severity' => 'INFO',
        'opened_at' => now()->subMinutes(1100),
    ]);

    expect($breachedUnknown->triagePriority())->toBe('P1')
        ->and($dueSoonUnknown->triagePriority())->toBe('P2');

    expect(
        SecurityIncident::query()->whereTriagePriority('P1')->pluck('id')
    )->toContain($breachedUnknown->id);

    expect(
        SecurityIncident::query()->whereTriagePriority('P2')->pluck('id')
    )->toContain($dueSoonUnknown->id);
});
