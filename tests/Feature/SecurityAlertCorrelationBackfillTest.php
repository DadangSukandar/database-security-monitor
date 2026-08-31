<?php

use App\Models\DatabaseConnection;
use App\Models\SecurityAlert;
use App\Models\SecurityAlertHistory;
use App\Models\VulnerabilityAssessment;
use App\Models\VulnerabilityFinding;
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
