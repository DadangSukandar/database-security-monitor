<?php

use App\Models\SecurityIncident;
use Carbon\Carbon;

test('critical active incident has p1 priority', function () {
    $now = Carbon::parse('2026-09-02 10:00:00');

    $incident = new SecurityIncident([
        'severity' => 'CRITICAL',
        'status' => 'OPEN',
        'opened_at' => $now->copy()->subMinutes(5),
    ]);

    expect($incident->triagePriority($now))->toBe('P1')
        ->and($incident->triagePriorityLabel($now))->toBe('Immediate');
});

test('breached sla promotes active incident to p1', function () {
    $now = Carbon::parse('2026-09-02 10:00:00');

    $incident = new SecurityIncident([
        'severity' => 'LOW',
        'status' => 'OPEN',
        'opened_at' => $now->copy()->subMinutes(1441),
    ]);

    expect($incident->responseSlaStatus($now))->toBe('BREACHED')
        ->and($incident->triagePriority($now))->toBe('P1')
        ->and($incident->triagePriorityLabel($now))->toBe('Immediate');
});

test('high active incident has p2 priority', function () {
    $now = Carbon::parse('2026-09-02 10:00:00');

    $incident = new SecurityIncident([
        'severity' => 'HIGH',
        'status' => 'OPEN',
        'opened_at' => $now->copy()->subMinutes(10),
    ]);

    expect($incident->responseSlaStatus($now))->toBe('ON_TRACK')
        ->and($incident->triagePriority($now))->toBe('P2')
        ->and($incident->triagePriorityLabel($now))->toBe('High');
});

test('due soon sla promotes active incident to p2', function () {
    $now = Carbon::parse('2026-09-02 10:00:00');

    $incident = new SecurityIncident([
        'severity' => 'MEDIUM',
        'status' => 'OPEN',
        'opened_at' => $now->copy()->subMinutes(190),
    ]);

    expect($incident->responseSlaStatus($now))->toBe('DUE_SOON')
        ->and($incident->triagePriority($now))->toBe('P2')
        ->and($incident->triagePriorityLabel($now))->toBe('High');
});

test('medium active incident has p3 priority while sla is on track', function () {
    $now = Carbon::parse('2026-09-02 10:00:00');

    $incident = new SecurityIncident([
        'severity' => 'MEDIUM',
        'status' => 'OPEN',
        'opened_at' => $now->copy()->subMinutes(30),
    ]);

    expect($incident->responseSlaStatus($now))->toBe('ON_TRACK')
        ->and($incident->triagePriority($now))->toBe('P3')
        ->and($incident->triagePriorityLabel($now))->toBe('Normal');
});

test('low active incident has p4 priority while sla is on track', function () {
    $now = Carbon::parse('2026-09-02 10:00:00');

    $incident = new SecurityIncident([
        'severity' => 'LOW',
        'status' => 'OPEN',
        'opened_at' => $now->copy()->subMinutes(30),
    ]);

    expect($incident->responseSlaStatus($now))->toBe('ON_TRACK')
        ->and($incident->triagePriority($now))->toBe('P4')
        ->and($incident->triagePriorityLabel($now))->toBe('Low');
});

test('closed incident has no triage priority', function () {
    $now = Carbon::parse('2026-09-02 10:00:00');

    $incident = new SecurityIncident([
        'severity' => 'CRITICAL',
        'status' => 'CLOSED',
        'opened_at' => $now->copy()->subDays(3),
        'closed_at' => $now->copy()->subDay(),
    ]);

    expect($incident->triagePriority($now))->toBe('NONE')
        ->and($incident->triagePriorityLabel($now))->toBe('Closed');
});

test('sla breach takes precedence over normal severity priority', function () {
    $now = Carbon::parse('2026-09-02 10:00:00');

    $incident = new SecurityIncident([
        'severity' => 'MEDIUM',
        'status' => 'INVESTIGATING',
        'opened_at' => $now->copy()->subMinutes(241),
    ]);

    expect($incident->responseSlaStatus($now))->toBe('BREACHED')
        ->and($incident->triagePriority($now))->toBe('P1');
});

test('due soon takes precedence over medium severity priority', function () {
    $now = Carbon::parse('2026-09-02 10:00:00');

    $incident = new SecurityIncident([
        'severity' => 'MEDIUM',
        'status' => 'INVESTIGATING',
        'opened_at' => $now->copy()->subMinutes(181),
    ]);

    expect($incident->responseSlaStatus($now))->toBe('DUE_SOON')
        ->and($incident->triagePriority($now))->toBe('P2');
});

test('unknown severity safely falls back to p4 while active', function () {
    $now = Carbon::parse('2026-09-02 10:00:00');

    $incident = new SecurityIncident([
        'severity' => 'UNKNOWN',
        'status' => 'OPEN',
        'opened_at' => $now->copy()->subMinutes(10),
    ]);

    expect($incident->triagePriority($now))->toBe('P4')
        ->and($incident->triagePriorityLabel($now))->toBe('Low');
});
