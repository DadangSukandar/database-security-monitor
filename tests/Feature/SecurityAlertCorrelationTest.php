<?php

use App\Models\DatabaseConnection;
use App\Models\SecurityAlert;
use App\Models\VulnerabilityAssessment;
use App\Models\VulnerabilityFinding;
use Illuminate\Support\Facades\Notification;

function createAssessmentFinding(
    DatabaseConnection $connection,
    string $ruleCode,
    string $title,
    string $severity,
    mixed $scannedAt
): VulnerabilityAssessment {
    $assessment = VulnerabilityAssessment::query()->create([
        'database_connection_id' => $connection->id,
        'database_name' => $connection->database,
        'scanned_at' => $scannedAt,
    ]);

    VulnerabilityFinding::query()->create([
        'vulnerability_assessment_id' => $assessment->id,
        'rule_code' => $ruleCode,
        'title' => $title,
        'severity' => $severity,
        'database_name' => $connection->database,
        'username' => 'security_subject',
        'host' => 'localhost',
    ]);

    return $assessment;
}

function createTestDatabaseConnection(): DatabaseConnection
{
    return DatabaseConnection::query()->create([
        'name' => 'Primary PostgreSQL',
        'driver' => 'pgsql',
        'host' => '127.0.0.1',
        'port' => 5432,
        'database' => 'guardium',
        'username' => 'scanner',
    ]);
}

it('correlates the same finding across assessments and remains idempotent per assessment', function () {
    Notification::fake();

    $connection = createTestDatabaseConnection();
    $firstAssessment = createAssessmentFinding(
        $connection,
        'PGSQL-ACCESS-001',
        'PostgreSQL superuser detected',
        'HIGH',
        now()->subHour()
    );

    $this->artisan('security:generate-alerts', ['--assessment' => $firstAssessment->id])
        ->assertSuccessful();

    $alert = SecurityAlert::query()->sole();

    expect($alert->fingerprint)->toHaveLength(64)
        ->and($alert->occurrence_count)->toBe(1)
        ->and($alert->last_assessment_id)->toBe($firstAssessment->id)
        ->and($alert->first_seen_at)->not->toBeNull()
        ->and($alert->last_seen_at)->not->toBeNull();

    $secondAssessment = createAssessmentFinding(
        $connection,
        'PGSQL-ACCESS-001',
        'Renamed superuser finding',
        'CRITICAL',
        now()
    );

    $this->artisan('security:generate-alerts', ['--assessment' => $secondAssessment->id])
        ->assertSuccessful();

    $correlatedAlert = $alert->fresh();

    expect(SecurityAlert::query()->count())->toBe(1)
        ->and($correlatedAlert->id)->toBe($alert->id)
        ->and($correlatedAlert->occurrence_count)->toBe(2)
        ->and($correlatedAlert->last_assessment_id)->toBe($secondAssessment->id)
        ->and($correlatedAlert->severity)->toBe('CRITICAL')
        ->and($correlatedAlert->title)->toBe('Renamed superuser finding');

    $this->artisan('security:generate-alerts', ['--assessment' => $secondAssessment->id])
        ->assertSuccessful();

    expect(SecurityAlert::query()->count())->toBe(1)
        ->and($alert->fresh()->occurrence_count)->toBe(2);
});

it('automatically reopens a resolved alert when the finding recurs', function () {
    Notification::fake();

    $connection = createTestDatabaseConnection();
    $firstAssessment = createAssessmentFinding(
        $connection,
        'PGSQL-ACCESS-001',
        'PostgreSQL superuser detected',
        'HIGH',
        now()->subDay()
    );

    $this->artisan('security:generate-alerts', ['--assessment' => $firstAssessment->id])
        ->assertSuccessful();

    $alert = SecurityAlert::query()->sole();
    $originalDetectedAt = $alert->detected_at;
    $alert->update([
        'status' => 'RESOLVED',
        'acknowledged_at' => now()->subHour(),
        'resolved_at' => now(),
        'resolution_note' => 'Privilege removed.',
    ]);

    $secondAssessment = createAssessmentFinding(
        $connection,
        'PGSQL-ACCESS-001',
        'PostgreSQL superuser detected',
        'HIGH',
        now()->addMinute()
    );

    $this->artisan('security:generate-alerts', ['--assessment' => $secondAssessment->id])
        ->assertSuccessful();

    $reopenedAlert = $alert->fresh();

    expect(SecurityAlert::query()->count())->toBe(1)
        ->and($reopenedAlert->status)->toBe('OPEN')
        ->and($reopenedAlert->occurrence_count)->toBe(2)
        ->and($reopenedAlert->last_assessment_id)->toBe($secondAssessment->id)
        ->and($reopenedAlert->detected_at->equalTo($originalDetectedAt))->toBeTrue()
        ->and($reopenedAlert->first_seen_at->equalTo($firstAssessment->scanned_at))->toBeTrue()
        ->and($reopenedAlert->last_seen_at->equalTo($secondAssessment->scanned_at))->toBeTrue()
        ->and($reopenedAlert->sla_started_at->equalTo($secondAssessment->scanned_at))->toBeTrue()
        ->and($reopenedAlert->responseSlaStatus($secondAssessment->scanned_at))->toBe('ON_TRACK')
        ->and($reopenedAlert->acknowledged_at)->toBeNull()
        ->and($reopenedAlert->resolved_at)->toBeNull()
        ->and($reopenedAlert->resolution_note)->toBeNull();

    $this->assertDatabaseHas('security_alert_histories', [
        'security_alert_id' => $alert->id,
        'action' => 'AUTO_REOPEN',
        'old_status' => 'RESOLVED',
        'new_status' => 'OPEN',
    ]);

    $this->artisan('security:generate-alerts', ['--assessment' => $secondAssessment->id])
        ->assertSuccessful();

    expect($alert->fresh()->occurrence_count)->toBe(2)
        ->and($alert->histories()->where('action', 'AUTO_REOPEN')->count())->toBe(1);
});

it('keeps different findings and security scopes in separate alerts', function () {
    Notification::fake();

    $primaryConnection = createTestDatabaseConnection();
    $secondaryConnection = DatabaseConnection::query()->create([
        'name' => 'Secondary PostgreSQL',
        'driver' => 'pgsql',
        'host' => '127.0.0.2',
        'port' => 5432,
        'database' => 'guardium',
        'username' => 'scanner',
    ]);

    $createFinding = function (
        DatabaseConnection $connection,
        string $databaseName,
        string $ruleCode,
        string $username
    ): VulnerabilityAssessment {
        $assessment = VulnerabilityAssessment::query()->create([
            'database_connection_id' => $connection->id,
            'database_name' => $databaseName,
            'scanned_at' => now(),
        ]);

        VulnerabilityFinding::query()->create([
            'vulnerability_assessment_id' => $assessment->id,
            'rule_code' => $ruleCode,
            'title' => $ruleCode.' finding',
            'severity' => 'HIGH',
            'database_name' => $databaseName,
            'username' => $username,
            'host' => 'localhost',
        ]);

        return $assessment;
    };

    $assessments = [
        $createFinding($primaryConnection, 'guardium', 'PGSQL-ACCESS-001', 'postgres'),
        $createFinding($primaryConnection, 'guardium', 'PGSQL-ACCESS-002', 'postgres'),
        $createFinding($primaryConnection, 'guardium', 'PGSQL-ACCESS-001', 'application_user'),
        $createFinding($primaryConnection, 'guardium_archive', 'PGSQL-ACCESS-001', 'postgres'),
        $createFinding($secondaryConnection, 'guardium', 'PGSQL-ACCESS-001', 'postgres'),
    ];

    foreach ($assessments as $assessment) {
        $this->artisan('security:generate-alerts', ['--assessment' => $assessment->id])
            ->assertSuccessful();
    }

    expect(SecurityAlert::query()->count())->toBe(5)
        ->and(SecurityAlert::query()->distinct()->count('fingerprint'))->toBe(5)
        ->and(SecurityAlert::query()->pluck('occurrence_count')->all())
        ->each->toBe(1);
});
