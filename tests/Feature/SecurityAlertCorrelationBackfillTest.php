<?php

use App\Models\DatabaseConnection;
use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Models\VulnerabilityAssessment;
use App\Models\VulnerabilityFinding;
use App\Services\SecurityAlertFingerprintService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

function createBackfillConnection(array $attributes = []): DatabaseConnection
{
    return DatabaseConnection::query()->create(array_merge([
        'name' => 'Historical PostgreSQL',
        'driver' => 'pgsql',
        'host' => '127.0.0.1',
        'port' => 5432,
        'database' => 'guardium',
        'username' => 'scanner',
    ], $attributes));
}

/** @return array{VulnerabilityAssessment, VulnerabilityFinding} */
function createBackfillSource(
    DatabaseConnection $connection,
    string $databaseName = 'guardium',
    string $ruleCode = 'PGSQL-ACCESS-001',
    string $username = 'postgres'
): array {
    $assessment = VulnerabilityAssessment::query()->create([
        'database_connection_id' => $connection->id,
        'database_name' => $databaseName,
        'scanned_at' => now()->subDay(),
    ]);

    $finding = VulnerabilityFinding::query()->create([
        'vulnerability_assessment_id' => $assessment->id,
        'rule_code' => $ruleCode,
        'title' => $ruleCode.' historical finding',
        'severity' => 'HIGH',
        'database_name' => $databaseName,
        'username' => $username,
        'host' => 'localhost',
    ]);

    return [$assessment, $finding];
}

function createLegacyAlert(
    VulnerabilityAssessment $assessment,
    VulnerabilityFinding $finding,
    array $attributes = []
): SecurityAlert {
    return SecurityAlert::query()->create(array_merge([
        'database_connection_id' => $assessment->database_connection_id,
        'database_name' => $finding->database_name,
        'username' => $finding->username,
        'client_ip' => $finding->host,
        'alert_type' => 'VULNERABILITY',
        'severity' => $finding->severity,
        'title' => $finding->title,
        'description' => '[Assessment #'.$assessment->id.'] [Finding #'.$finding->id.'] Historical finding.',
        'status' => 'OPEN',
        'detected_at' => now()->subDay(),
    ], $attributes));
}

beforeEach(function () {
    Notification::fake();
    config()->set('services.security_alerts.recipients', []);
});

it('defaults to dry run and does not change historical alerts', function () {
    [$assessment, $finding] = createBackfillSource(createBackfillConnection());
    $alert = createLegacyAlert($assessment, $finding);
    $original = $alert->fresh()->getAttributes();

    $this->artisan('security:backfill-alert-correlation')
        ->expectsOutputToContain('Mode                       : DRY RUN')
        ->expectsOutputToContain('Safe backfills             : 1')
        ->assertSuccessful();

    expect($alert->fresh()->getAttributes())->toBe($original);
});

it('backfills a safe alert once while preserving history and occurrence timestamps', function () {
    [$assessment, $finding] = createBackfillSource(createBackfillConnection());
    $earliest = now()->subDays(3)->startOfSecond();
    $latest = now()->subDay()->startOfSecond();
    $alert = createLegacyAlert($assessment, $finding, [
        'occurrence_count' => 4,
        'first_seen_at' => $earliest,
        'last_seen_at' => $latest,
        'detected_at' => now()->subDays(2)->startOfSecond(),
        'status' => 'ACKNOWLEDGED',
        'acknowledged_at' => now()->subDay(),
    ]);

    $history = SecurityAlertHistory::query()->create([
        'security_alert_id' => $alert->id,
        'action' => 'ACKNOWLEDGE',
        'old_status' => 'OPEN',
        'new_status' => 'ACKNOWLEDGED',
    ]);

    $this->artisan('security:backfill-alert-correlation', ['--apply' => true])
        ->expectsOutputToContain('Updated alerts             : 1')
        ->assertSuccessful();

    $backfilled = $alert->fresh();

    expect($backfilled->fingerprint)->toHaveLength(64)
        ->and($backfilled->occurrence_count)->toBe(4)
        ->and($backfilled->first_seen_at->equalTo($earliest))->toBeTrue()
        ->and($backfilled->last_seen_at->equalTo($latest))->toBeTrue()
        ->and($backfilled->last_assessment_id)->toBe($assessment->id)
        ->and($backfilled->status)->toBe('ACKNOWLEDGED')
        ->and($backfilled->acknowledged_at)->not->toBeNull()
        ->and($history->fresh())->not->toBeNull()
        ->and($backfilled->histories()->count())->toBe(1);

    $snapshot = $backfilled->getAttributes();

    $this->artisan('security:backfill-alert-correlation', ['--apply' => true])
        ->expectsOutputToContain('Updated alerts             : 0')
        ->assertSuccessful();

    expect($alert->fresh()->getAttributes())->toBe($snapshot)
        ->and($alert->histories()->count())->toBe(1);
});

it('uses the same fingerprint behavior as alert generation', function () {
    [$assessment, $finding] = createBackfillSource(createBackfillConnection());
    $alert = createLegacyAlert($assessment, $finding);

    $this->artisan('security:backfill-alert-correlation', ['--apply' => true])
        ->assertSuccessful();

    $backfillFingerprint = $alert->fresh()->fingerprint;

    $alert->update(['fingerprint' => null]);

    $this->artisan('security:generate-alerts', ['--assessment' => $assessment->id])
        ->assertSuccessful();

    expect(SecurityAlert::query()->count())->toBe(1)
        ->and($alert->fresh()->fingerprint)->toBe($backfillFingerprint);
});

it('does not correlate historical alerts from different scopes', function () {
    $primaryConnection = createBackfillConnection();
    $secondaryConnection = createBackfillConnection([
        'name' => 'Secondary PostgreSQL',
        'host' => '127.0.0.2',
    ]);

    $sources = [
        createBackfillSource($primaryConnection),
        createBackfillSource($primaryConnection, 'guardium', 'PGSQL-ACCESS-001', 'application_user'),
        createBackfillSource($primaryConnection, 'guardium_archive'),
        createBackfillSource($secondaryConnection),
        createBackfillSource($primaryConnection, 'guardium', 'PGSQL-ACCESS-002'),
    ];

    foreach ($sources as [$assessment, $finding]) {
        createLegacyAlert($assessment, $finding);
    }

    $this->artisan('security:backfill-alert-correlation', ['--apply' => true])
        ->assertSuccessful();

    expect(SecurityAlert::query()->whereNotNull('fingerprint')->count())->toBe(5)
        ->and(SecurityAlert::query()->distinct()->count('fingerprint'))->toBe(5);
});

it('skips duplicate and untraceable historical alerts without losing history', function () {
    [$assessment, $finding] = createBackfillSource(createBackfillConnection());
    $first = createLegacyAlert($assessment, $finding);
    $second = createLegacyAlert($assessment, $finding);
    $untraceable = createLegacyAlert($assessment, $finding, [
        'description' => 'Legacy description without source identifiers.',
    ]);

    SecurityAlertHistory::query()->create([
        'security_alert_id' => $second->id,
        'action' => 'RESOLVE',
        'old_status' => 'OPEN',
        'new_status' => 'RESOLVED',
    ]);

    $this->artisan('security:backfill-alert-correlation', ['--apply' => true])
        ->expectsOutputToContain('Duplicate groups           : 1')
        ->expectsOutputToContain('Ambiguous/skipped          : 3')
        ->expectsOutputToContain('Duplicate alerts involved  : 2')
        ->expectsOutputToContain('Potential redundant alerts : 1')
        ->expectsOutputToContain('Missing source reference     : 1')
        ->expectsOutputToContain('Collision/uncertain          : 2')
        ->expectsOutputToContain('Breakdown total              : 3')
        ->expectsOutputToContain('Updated alerts             : 0')
        ->assertSuccessful();

    expect($first->fresh()->fingerprint)->toBeNull()
        ->and($second->fresh()->fingerprint)->toBeNull()
        ->and($untraceable->fresh()->fingerprint)->toBeNull()
        ->and($second->histories()->count())->toBe(1)
        ->and(SecurityAlert::query()->count())->toBe(3);
});

it('does not modify existing correlated alerts', function () {
    $alert = SecurityAlert::query()->create([
        'alert_type' => 'VULNERABILITY',
        'fingerprint' => str_repeat('a', 64),
        'severity' => 'CRITICAL',
        'title' => 'Existing correlated alert',
        'status' => 'RESOLVED',
        'occurrence_count' => 9,
        'first_seen_at' => now()->subWeek(),
        'last_seen_at' => now()->subDay(),
        'detected_at' => now()->subWeek(),
        'resolved_at' => now(),
        'resolution_note' => 'Already handled.',
    ]);
    $snapshot = $alert->fresh()->getAttributes();

    $this->artisan('security:backfill-alert-correlation', ['--apply' => true])
        ->expectsOutputToContain('Updated alerts             : 0')
        ->assertSuccessful();

    expect($alert->fresh()->getAttributes())->toBe($snapshot);
});

it('builds a chronological non destructive consolidation plan', function () {
    $connection = createBackfillConnection();
    [$firstAssessment, $firstFinding] = createBackfillSource($connection);
    [$secondAssessment, $secondFinding] = createBackfillSource($connection);
    [$thirdAssessment, $thirdFinding] = createBackfillSource($connection);
    $baseTime = now()->subDay()->startOfSecond();
    $fingerprint = app(SecurityAlertFingerprintService::class)->forVulnerabilityFinding(
        $connection->id,
        'guardium',
        $thirdFinding
    );

    $canonical = createLegacyAlert($thirdAssessment, $thirdFinding, [
        'fingerprint' => $fingerprint,
        'severity' => 'MEDIUM',
        'detected_at' => $baseTime,
        'first_seen_at' => $baseTime,
        'last_seen_at' => $baseTime,
        'last_assessment_id' => $thirdAssessment->id,
    ]);
    $resolvedDuplicate = createLegacyAlert($firstAssessment, $firstFinding, [
        'status' => 'RESOLVED',
        'severity' => 'HIGH',
        'detected_at' => $baseTime->copy()->addHour(),
        'acknowledged_at' => $baseTime->copy()->addHours(2),
        'resolved_at' => $baseTime->copy()->addHours(3),
        'resolution_note' => 'Historical remediation.',
    ]);
    $latestDuplicate = createLegacyAlert($secondAssessment, $secondFinding, [
        'severity' => 'CRITICAL',
        'detected_at' => $baseTime->copy()->addHours(4),
        'last_seen_at' => $baseTime->copy()->addHours(5),
    ]);
    $history = SecurityAlertHistory::query()->create([
        'security_alert_id' => $resolvedDuplicate->id,
        'action' => 'RESOLVE',
        'old_status' => 'ACKNOWLEDGED',
        'new_status' => 'RESOLVED',
    ]);
    $history->forceFill([
        'created_at' => $baseTime->copy()->addHours(3),
        'updated_at' => $baseTime->copy()->addHours(3),
    ])->saveQuietly();
    $alertSnapshot = SecurityAlert::query()->orderBy('id')->get()->map->getAttributes()->all();
    $historySnapshot = SecurityAlertHistory::query()->orderBy('id')->get()->map->getAttributes()->all();

    $this->artisan('security:backfill-alert-correlation', [
        '--consolidate' => true,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Groups                         : 1')
        ->expectsOutputToContain('Canonical alerts               : 1')
        ->expectsOutputToContain('Historical duplicates          : 2')
        ->expectsOutputToContain('Distinct occurrences           : 3')
        ->expectsOutputToContain('Histories preserved            : 1')
        ->expectsOutputToContain('References requiring migration : 0')
        ->expectsOutputToContain('Unsafe groups                  : 0')
        ->expectsOutputToContain('Ready groups                   : 1')
        ->expectsOutputToContain('Canonical ID        : '.$canonical->id)
        ->expectsOutputToContain('Duplicate IDs       : '.$resolvedDuplicate->id.', '.$latestDuplicate->id)
        ->expectsOutputToContain('Occurrence count    : 3')
        ->expectsOutputToContain('First seen          : '.$baseTime->format('Y-m-d H:i:s'))
        ->expectsOutputToContain('Last seen           : '.$baseTime->copy()->addHours(5)->format('Y-m-d H:i:s'))
        ->expectsOutputToContain('Final status        : OPEN')
        ->expectsOutputToContain('Highest severity    : CRITICAL')
        ->expectsOutputToContain('Latest assessment   : '.$secondAssessment->id)
        ->expectsOutputToContain('Acknowledged        : YES')
        ->expectsOutputToContain('Resolved            : YES')
        ->expectsOutputToContain('Resolution note     : YES')
        ->expectsOutputToContain('Safety result       : READY')
        ->assertSuccessful();

    expect(SecurityAlert::query()->orderBy('id')->get()->map->getAttributes()->all())->toBe($alertSnapshot)
        ->and(SecurityAlertHistory::query()->orderBy('id')->get()->map->getAttributes()->all())->toBe($historySnapshot)
        ->and(SecurityAlert::query()->count())->toBe(3)
        ->and($history->fresh()->security_alert_id)->toBe($resolvedDuplicate->id);
});

it('counts distinct assessment occurrences instead of alert rows', function () {
    $connection = createBackfillConnection();
    [$assessment, $finding] = createBackfillSource($connection);
    $fingerprint = app(SecurityAlertFingerprintService::class)->forVulnerabilityFinding(
        $connection->id,
        'guardium',
        $finding
    );

    createLegacyAlert($assessment, $finding, [
        'fingerprint' => $fingerprint,
        'last_assessment_id' => $assessment->id,
    ]);
    createLegacyAlert($assessment, $finding);
    createLegacyAlert($assessment, $finding);

    $this->artisan('security:backfill-alert-correlation', [
        '--consolidate' => true,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Historical duplicates          : 2')
        ->expectsOutputToContain('Distinct occurrences           : 1')
        ->expectsOutputToContain('Occurrence count    : 1')
        ->assertSuccessful();
});

it('consolidates groups non destructively and remains idempotent', function () {
    $connection = createBackfillConnection();

    foreach (['PGSQL-ACCESS-001', 'PGSQL-ACCESS-002'] as $ruleCode) {
        [$assessment, $finding] = createBackfillSource($connection, 'guardium', $ruleCode);
        $fingerprint = app(SecurityAlertFingerprintService::class)->forVulnerabilityFinding(
            $connection->id,
            'guardium',
            $finding
        );

        createLegacyAlert($assessment, $finding, [
            'fingerprint' => $fingerprint,
            'last_assessment_id' => $assessment->id,
        ]);
        createLegacyAlert($assessment, $finding);
    }

    $parameters = ['--consolidate' => true, '--dry-run' => true];

    expect(Artisan::call('security:backfill-alert-correlation', $parameters))->toBe(0);
    $firstOutput = Artisan::output();
    expect(Artisan::call('security:backfill-alert-correlation', $parameters))->toBe(0);
    $secondOutput = Artisan::output();

    expect($firstOutput)->toBe($secondOutput)
        ->and($firstOutput)->toContain('Groups                         : 2')
        ->and($firstOutput)->toContain('Historical duplicates          : 2');

    $this->artisan('security:backfill-alert-correlation', [
        '--consolidate' => true,
        '--apply' => true,
    ])->assertSuccessful();

    $canonicals = SecurityAlert::query()->canonical()->orderBy('id')->get();
    $duplicates = SecurityAlert::query()->whereNotNull('canonical_alert_id')->orderBy('id')->get();

    expect(SecurityAlert::query()->count())->toBe(4)
        ->and($canonicals)->toHaveCount(2)
        ->and($duplicates)->toHaveCount(2)
        ->and($duplicates->every(fn (SecurityAlert $alert): bool => $alert->canonical_alert_id !== $alert->id))->toBeTrue()
        ->and($duplicates->every(fn (SecurityAlert $alert): bool => $alert->canonicalAlert !== null))->toBeTrue()
        ->and($canonicals->every(fn (SecurityAlert $alert): bool => $alert->historicalDuplicates()->count() === 1))->toBeTrue()
        ->and(SecurityAlertHistory::query()->where('action', 'HISTORICAL_CONSOLIDATION')->count())->toBe(2);

    $this->artisan('security:backfill-alert-correlation', [
        '--consolidate' => true,
        '--apply' => true,
    ])->assertSuccessful();

    expect(SecurityAlert::query()->count())->toBe(4)
        ->and(SecurityAlertHistory::query()->where('action', 'HISTORICAL_CONSOLIDATION')->count())->toBe(2);
});

it('rolls back every group when a later consolidation group fails', function () {
    $connection = createBackfillConnection();
    $groups = collect();

    foreach (['PGSQL-ATOMIC-001', 'PGSQL-ATOMIC-002'] as $index => $ruleCode) {
        [$assessment, $finding] = createBackfillSource($connection, 'guardium', $ruleCode);
        $fingerprint = app(SecurityAlertFingerprintService::class)->forVulnerabilityFinding(
            $connection->id,
            'guardium',
            $finding
        );
        $canonical = createLegacyAlert($assessment, $finding, [
            'fingerprint' => $fingerprint,
            'occurrence_count' => 7 + $index,
            'last_assessment_id' => $assessment->id,
        ]);
        $duplicate = createLegacyAlert($assessment, $finding);

        $groups->push(compact('canonical', 'duplicate'));
    }

    $beforeAlerts = SecurityAlert::query()->orderBy('id')->get()->map->getAttributes()->all();
    $secondDuplicateId = $groups->get(1)['duplicate']->id;

    DB::statement(<<<SQL
        CREATE TRIGGER fail_second_correlation_group
        BEFORE UPDATE ON security_alerts
        WHEN OLD.id = {$secondDuplicateId} AND NEW.canonical_alert_id IS NOT NULL
        BEGIN
            SELECT RAISE(ABORT, 'forced second group failure');
        END
    SQL);

    try {
        $this->artisan('security:backfill-alert-correlation', [
            '--consolidate' => true,
            '--apply' => true,
        ])
            ->expectsOutputToContain('Historical consolidation failed and all changes were rolled back.')
            ->assertFailed();
    } finally {
        DB::statement('DROP TRIGGER IF EXISTS fail_second_correlation_group');
    }

    expect(SecurityAlert::query()->orderBy('id')->get()->map->getAttributes()->all())->toBe($beforeAlerts)
        ->and(SecurityAlert::query()->whereNotNull('canonical_alert_id')->count())->toBe(0)
        ->and(SecurityAlert::query()->whereNotNull('consolidated_at')->count())->toBe(0)
        ->and(SecurityAlertHistory::query()->where('action', 'HISTORICAL_CONSOLIDATION')->count())->toBe(0);
});

it('uses only canonical alerts for generation SLA and dashboard analytics', function () {
    $connection = createBackfillConnection();
    [$assessment, $finding] = createBackfillSource($connection);
    $fingerprint = app(SecurityAlertFingerprintService::class)->forVulnerabilityFinding(
        $connection->id,
        'guardium',
        $finding
    );
    $canonical = createLegacyAlert($assessment, $finding, [
        'fingerprint' => $fingerprint,
        'occurrence_count' => 1,
        'last_assessment_id' => $assessment->id,
        'severity' => 'CRITICAL',
        'detected_at' => now()->subMinutes(30),
    ]);
    $duplicate = createLegacyAlert($assessment, $finding, [
        'canonical_alert_id' => $canonical->id,
        'consolidated_at' => now(),
        'severity' => 'CRITICAL',
        'detected_at' => now()->subMinutes(30),
    ]);

    $this->artisan('security:escalate-alerts')
        ->expectsOutput('Escalated 0 security alert(s).')
        ->assertSuccessful();

    $this->get(route('security-dashboard'))
        ->assertOk()
        ->assertViewHas('totalAlerts', 1)
        ->assertViewHas('breachedSlaAlerts', 1);

    [$nextAssessment] = createBackfillSource($connection);

    $this->artisan('security:generate-alerts', ['--assessment' => $nextAssessment->id])
        ->assertSuccessful();

    expect(SecurityAlert::query()->count())->toBe(2)
        ->and($canonical->fresh()->occurrence_count)->toBe(2)
        ->and($canonical->fresh()->last_assessment_id)->toBe($nextAssessment->id)
        ->and($duplicate->fresh()->occurrence_count)->toBe(1);
});
