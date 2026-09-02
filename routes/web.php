<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseActivityController;
use App\Http\Controllers\DatabaseConnectionController;
use App\Http\Controllers\DatabaseDiscoveryController;
use App\Http\Controllers\DatabaseExplorerController;
use App\Http\Controllers\DatabasePrivilegeController;
use App\Http\Controllers\DatabaseQueryController;
use App\Http\Controllers\DatabaseUserController;
use App\Http\Controllers\QueryHistoryController;
use App\Http\Controllers\SecurityAlertController;
use App\Http\Controllers\SecurityAuditController;
use App\Http\Controllers\SecurityDashboardController;
use App\Http\Controllers\SecurityFindingController;
use App\Http\Controllers\SecurityIncidentController;
use App\Http\Controllers\SecurityPolicyController;
use App\Http\Controllers\SecurityReportController;
use App\Http\Controllers\SecurityRiskController;
use App\Http\Controllers\SensitiveDataDiscoveryController;
use App\Http\Controllers\SqlQueryController;
use App\Http\Controllers\VulnerabilityAssessmentController;
use App\Http\Middleware\EnsureCurrentTeamMembership;
use Illuminate\Support\Facades\Route;

Route::pattern('alert', '[0-9]+');
Route::pattern('assessment', '[0-9]+');
Route::pattern('databaseActivity', '[0-9]+');
Route::pattern('databaseConnection', '[0-9]+');
Route::pattern('database_connection', '[0-9]+');
Route::pattern('discoveredDatabase', '[0-9]+');
Route::pattern('discoveredTable', '[0-9]+');
Route::pattern('finding', '[0-9]+');
Route::pattern('securityFinding', '[0-9]+');

Route::get(
    '/',
    [DashboardController::class, 'index']
)->name('home');

Route::get(
    '/dashboard',
    [DashboardController::class, 'index']
)->name('dashboard');

Route::middleware(['auth', EnsureCurrentTeamMembership::class])->group(function () {
    Route::resource(
        'database-connections',
        DatabaseConnectionController::class
    )->only([
        'index',
        'create',
        'store',
        'show',
        'destroy',
    ])->middleware('security.team:admin');

    Route::post(
        '/database-connections/{databaseConnection}/test',
        [
            DatabaseConnectionController::class,
            'test',
        ]
    )->middleware('security.team:admin')->name(
        'database-connections.test'
    );

    Route::post(
        '/database-connections/{databaseConnection}/scan',
        [
            DatabaseConnectionController::class,
            'scan',
        ]
    )->middleware('security.team:admin')->name(
        'database-connections.scan'
    );

    Route::get(
        '/database-explorer/{databaseConnection}/{table}',
        [
            DatabaseExplorerController::class,
            'show',
        ]
    )->middleware('security.team:admin')->name('database-explorer.show');

    Route::get(
        '/database-activities',
        [
            DatabaseActivityController::class,
            'index',
        ]
    )->name(
        'database-activities.index'
    );

    Route::get(
        '/database-activities/{databaseActivity}',
        [
            DatabaseActivityController::class,
            'show',
        ]
    )->name(
        'database-activities.show'
    );

    Route::get(
        '/database-discovery',
        [
            DatabaseDiscoveryController::class,
            'index',
        ]
    )->middleware('security.team:admin')->name(
        'database-discovery.index'
    );

    Route::post(
        '/database-discovery/scan/{databaseConnection}',
        [
            DatabaseDiscoveryController::class,
            'scan',
        ]
    )->middleware('security.team:admin')->name(
        'database-discovery.scan'
    );

    Route::get(
        '/database-discovery/database/{discoveredDatabase}',
        [
            DatabaseDiscoveryController::class,
            'show',
        ]
    )->middleware('security.team:admin')->name(
        'database-discovery.show'
    );

    Route::get(
        '/database-discovery/table/{discoveredTable}',
        [
            DatabaseDiscoveryController::class,
            'table',
        ]
    )->middleware('security.team:admin')->name(
        'database-discovery.table'
    );

    Route::get(
        '/sensitive-data',
        [
            SensitiveDataDiscoveryController::class,
            'index',
        ]
    )->middleware('security.team:admin')->name(
        'sensitive-data.index'
    );

    Route::post(
        '/sensitive-data/scan',
        [
            SensitiveDataDiscoveryController::class,
            'scan',
        ]
    )->middleware('security.team:admin')->name(
        'sensitive-data.scan'
    );
    Route::get(
        '/database-query',
        [
            DatabaseQueryController::class,
            'index',
        ]
    )->middleware('security.team:admin')->name(
        'database-query.index'
    );

    Route::post(
        '/database-query/execute',
        [
            DatabaseQueryController::class,
            'execute',
        ]
    )->middleware('security.team:admin')->name(
        'database-query.execute'
    );

    Route::get(
        '/database-users',
        [
            DatabaseUserController::class,
            'index',
        ]
    )->middleware('security.team:admin')->name(
        'database-users.index'
    );

    Route::post(
        '/database-connections/{databaseConnection}/users/scan',
        [
            DatabaseUserController::class,
            'scan',
        ]
    )->middleware('security.team:admin')->name(
        'database-users.scan'
    );

    Route::get(
        '/database-privileges',
        [
            DatabasePrivilegeController::class,
            'index',
        ]
    )->middleware('security.team:admin')->name(
        'database-privileges.index'
    );

    Route::post(
        '/database-connections/{databaseConnection}/privileges/scan',
        [
            DatabasePrivilegeController::class,
            'scan',
        ]
    )->middleware('security.team:admin')->name(
        'database-privileges.scan'
    );

    Route::prefix('security-audit')
        ->name('security-audit.')
        ->group(function () {

            Route::get(
                '/',
                [SecurityAuditController::class, 'index']
            )->name('index');

            Route::get(
                '/{securityFinding}',
                [SecurityAuditController::class, 'show']
            )->name('show');

            Route::post(
                '/{securityFinding}/resolve',
                [SecurityAuditController::class, 'resolve']
            )->name('resolve');

            Route::post(
                '/{securityFinding}/ignore',
                [SecurityAuditController::class, 'ignore']
            )->name('ignore');

            Route::post(
                '/{securityFinding}/reopen',
                [SecurityAuditController::class, 'reopen']
            )->name('reopen');

            Route::post(
                '/scan',
                [SecurityAuditController::class, 'scan']
            )->middleware('security.team:admin')->name('scan');

        });

    Route::get(
        '/sql-query',
        [SqlQueryController::class, 'index']
    )->middleware('security.team:admin')->name('sql-query.index');

    Route::post(
        '/sql-query/execute',
        [SqlQueryController::class, 'execute']
    )->middleware('security.team:admin')->name('sql-query.execute');

    Route::get(
        '/query-history',
        [QueryHistoryController::class, 'index']
    )->middleware('security.team:admin')->name('query-history.index');

    Route::get(
        '/query-history/{databaseActivity}',
        [QueryHistoryController::class, 'show']
    )->middleware('security.team:admin')->name('query-history.show');

    Route::prefix('security-alerts')
        ->name('security-alerts.')
        ->group(function () {

            Route::get(
                '/',
                [SecurityAlertController::class, 'index']
            )->name('index');

            Route::get(
                '/{alert}',
                [SecurityAlertController::class, 'show']
            )->name('show');

            Route::post(
                '/{alert}/acknowledge',
                [SecurityAlertController::class, 'acknowledge']
            )->name('acknowledge');

            Route::post(
                '/{alert}/investigate',
                [SecurityAlertController::class, 'investigate']
            )->name('investigate');

            Route::post(
                '/{alert}/investigation-notes',
                [SecurityAlertController::class, 'addInvestigationNote']
            )->name('investigation-notes.store');

            Route::post(
                '/{alert}/assign',
                [SecurityAlertController::class, 'assign']
            )->name('assign');

            Route::post(
                '/{alert}/unassign',
                [SecurityAlertController::class, 'unassign']
            )->name('unassign');

            Route::post(
                '/{alert}/resolve',
                [SecurityAlertController::class, 'resolve']
            )->name('resolve');

            Route::post(
                '/{alert}/reopen',
                [SecurityAlertController::class, 'reopen']
            )->name('reopen');

            Route::post(
                '/{alert}/escalate-to-incident',
                [SecurityAlertController::class, 'escalateToIncident']
            )->name('escalate-to-incident');

        });

    Route::prefix('security-incidents')
        ->name('security-incidents.')
        ->group(function () {

            Route::get(
                '/',
                [SecurityIncidentController::class, 'index']
            )->name('index');

            Route::get(
                '/{incident}',
                [SecurityIncidentController::class, 'show']
            )->name('show');

            Route::post(
                '/{incident}/acknowledge',
                [SecurityIncidentController::class, 'acknowledge']
            )->name('acknowledge');

            Route::post(
                '/{incident}/investigate',
                [SecurityIncidentController::class, 'investigate']
            )->name('investigate');

            Route::post(
                '/{incident}/contain',
                [SecurityIncidentController::class, 'contain']
            )->name('contain');

            Route::post(
                '/{incident}/resolve',
                [SecurityIncidentController::class, 'resolve']
            )->name('resolve');

            Route::post(
                '/{incident}/close',
                [SecurityIncidentController::class, 'close']
            )->name('close');

            Route::post(
                '/{incident}/assign',
                [SecurityIncidentController::class, 'assign']
            )->name('assign');

            Route::post(
                '/{incident}/unassign',
                [SecurityIncidentController::class, 'unassign']
            )->name('unassign');

            Route::post(
                '/{incident}/investigation-notes',
                [SecurityIncidentController::class, 'addInvestigationNote']
            )->name('investigation-notes.store');

        });

    Route::prefix('security-policies')
        ->name('security-policies.')
        ->middleware('security.team:admin')
        ->group(function () {

            Route::get(
                '/',
                [SecurityPolicyController::class, 'index']
            )->name('index');

            Route::get(
                '/create',
                [SecurityPolicyController::class, 'create']
            )->name('create');

            Route::post(
                '/',
                [SecurityPolicyController::class, 'store']
            )->name('store');

            Route::get(
                '/{securityPolicy}/edit',
                [SecurityPolicyController::class, 'edit']
            )->name('edit');

            Route::put(
                '/{securityPolicy}',
                [SecurityPolicyController::class, 'update']
            )->name('update');

            Route::delete(
                '/{securityPolicy}',
                [SecurityPolicyController::class, 'destroy']
            )->name('destroy');

            Route::post(
                '/{securityPolicy}/toggle',
                [SecurityPolicyController::class, 'toggle']
            )->name('toggle');
        });

    Route::prefix('vulnerability-assessments')
        ->name('vulnerability-assessments.')
        ->group(function () {

            Route::get(
                '/',
                [VulnerabilityAssessmentController::class, 'index']
            )->name('index');

            Route::post(
                '/scan',
                [VulnerabilityAssessmentController::class, 'scan']
            )->middleware('security.team:admin')->name('scan');

            Route::get(
                '/{assessment}',
                [VulnerabilityAssessmentController::class, 'show']
            )->name('show');

        });

    Route::prefix('security-reports')
        ->name('security-reports.')
        ->group(function () {

            /*
             * Daftar laporan security
             */
            Route::get(
                '/',
                [SecurityReportController::class, 'index']
            )->name('index');

            /*
             * Detail laporan
             */
            Route::get(
                '/{assessment}',
                [SecurityReportController::class, 'show']
            )->name('show');

            /*
             * Print laporan
             */
            Route::get(
                '/{assessment}/print',
                [SecurityReportController::class, 'print']
            )->name('print');

            Route::post(
                '/{assessment}/rerun',
                [SecurityReportController::class, 'rerun']
            )->middleware('security.team:admin')->name('rerun');

            Route::get(
                '/{assessment}/comparison',
                [SecurityReportController::class, 'comparison']
            )->name('comparison');

        });

    Route::prefix('security-findings')
        ->name('security-findings.')
        ->group(function () {

            Route::get(
                '/',
                [SecurityFindingController::class, 'index']
            )->name('index');

            Route::get(
                '/{finding}',
                [SecurityFindingController::class, 'show']
            )->name('show');

            Route::post(
                '/{finding}/resolve',
                [SecurityFindingController::class, 'resolve']
            )->name('resolve');

            Route::post(
                '/{finding}/ignore',
                [SecurityFindingController::class, 'ignore']
            )->name('ignore');

            Route::post(
                '/{finding}/reopen',
                [SecurityFindingController::class, 'reopen']
            )->name('reopen');
        });

    Route::get(
        '/security-risk',
        [SecurityRiskController::class, 'index']
    )->name('security-risk.index');

    Route::get(
        '/security-dashboard',
        [SecurityDashboardController::class, 'index']
    )->name('security-dashboard');
});

/*
|--------------------------------------------------------------------------
| Settings / Profile / Teams Routes
|--------------------------------------------------------------------------
|
| Route bawaan untuk profile, security, teams, members,
| dan team invitations.
|
*/

require __DIR__.'/settings.php';
