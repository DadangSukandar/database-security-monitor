<?php

use App\Models\SecurityAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('sql sla scope matches model sla status', function () {
    Carbon::setTestNow(
        Carbon::parse('2026-09-03 12:00:00')
    );

    try {
        $now = now();

        $alerts = collect([
            SecurityAlert::query()->create([
                'alert_type' => 'VULNERABILITY',
                'severity' => 'CRITICAL',
                'title' => 'Unknown SLA alert',
                'status' => 'OPEN',
                'detected_at' => null,
                'sla_started_at' => null,
            ]),

            SecurityAlert::query()->create([
                'alert_type' => 'VULNERABILITY',
                'severity' => 'HIGH',
                'title' => 'Met SLA alert',
                'status' => 'ACKNOWLEDGED',
                'detected_at' => $now->copy()->subMinutes(30),
                'acknowledged_at' => $now->copy()->subMinutes(10),
            ]),

            SecurityAlert::query()->create([
                'alert_type' => 'VULNERABILITY',
                'severity' => 'CRITICAL',
                'title' => 'Late acknowledgement alert',
                'status' => 'ACKNOWLEDGED',
                'detected_at' => $now->copy()->subMinutes(30),
                'acknowledged_at' => $now->copy()->subMinutes(5),
            ]),

            SecurityAlert::query()->create([
                'alert_type' => 'VULNERABILITY',
                'severity' => 'CRITICAL',
                'title' => 'Resolved without acknowledgement',
                'status' => 'RESOLVED',
                'detected_at' => $now->copy()->subMinutes(5),
                'resolved_at' => $now->copy()->subMinute(),
            ]),

            SecurityAlert::query()->create([
                'alert_type' => 'VULNERABILITY',
                'severity' => 'CRITICAL',
                'title' => 'Open breached alert',
                'status' => 'OPEN',
                'detected_at' => $now->copy()->subMinutes(30),
            ]),

            SecurityAlert::query()->create([
                'alert_type' => 'VULNERABILITY',
                'severity' => 'CRITICAL',
                'title' => 'Due soon alert',
                'status' => 'OPEN',
                'detected_at' => $now->copy()->subMinutes(12),
            ]),

            SecurityAlert::query()->create([
                'alert_type' => 'VULNERABILITY',
                'severity' => 'HIGH',
                'title' => 'On track alert',
                'status' => 'OPEN',
                'detected_at' => $now->copy()->subMinutes(10),
            ]),

            SecurityAlert::query()->create([
                'alert_type' => 'VULNERABILITY',
                'severity' => 'CRITICAL',
                'title' => 'Restarted SLA cycle',
                'status' => 'OPEN',
                'detected_at' => $now->copy()->subHours(3),
                'sla_started_at' => $now->copy()->subMinutes(5),
            ]),

            SecurityAlert::query()->create([
                'alert_type' => 'VULNERABILITY',
                'severity' => 'UNKNOWN_SEVERITY',
                'title' => 'Unknown severity fallback alert',
                'status' => 'OPEN',
                'detected_at' => $now->copy()->subMinutes(10),
            ]),
        ]);

        foreach (
            ['UNKNOWN', 'MET', 'BREACHED', 'DUE_SOON', 'ON_TRACK'] as $status
        ) {
            $phpIds = $alerts
                ->filter(
                    fn (SecurityAlert $alert): bool => $alert->responseSlaStatus($now) === $status
                )
                ->pluck('id')
                ->sort()
                ->values()
                ->all();

            $sqlIds = SecurityAlert::query()
                ->whereResponseSlaStatus($status, $now)
                ->pluck('id')
                ->sort()
                ->values()
                ->all();

            expect($sqlIds)->toBe(
                $phpIds,
                "SQL SLA scope mismatch for status {$status}"
            );
        }
    } finally {
        Carbon::setTestNow();
    }
});
