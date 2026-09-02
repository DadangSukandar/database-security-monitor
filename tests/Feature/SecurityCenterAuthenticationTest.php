<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

it('protects security center read routes from guests', function (string $routeName) {
    $this->get(route($routeName))
        ->assertRedirect(route('login'));
})->with([
    'database connections' => 'database-connections.index',
    'database connection create' => 'database-connections.create',
    'database activities' => 'database-activities.index',
    'database discovery' => 'database-discovery.index',
    'sensitive data' => 'sensitive-data.index',
    'database query' => 'database-query.index',
    'database users' => 'database-users.index',
    'database privileges' => 'database-privileges.index',
    'security audit' => 'security-audit.index',
    'sql query' => 'sql-query.index',
    'query history' => 'query-history.index',
    'security alerts' => 'security-alerts.index',
    'security incidents' => 'security-incidents.index',
    'security policies' => 'security-policies.index',
    'vulnerability assessments' => 'vulnerability-assessments.index',
    'security reports' => 'security-reports.index',
    'security findings' => 'security-findings.index',
    'security risk' => 'security-risk.index',
    'security dashboard' => 'security-dashboard',
]);

it('rejects authenticated users whose current team membership is invalid', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $team->members()->detach($user);

    $this->actingAs($user)
        ->get(route('security-alerts.index'))
        ->assertForbidden();
});

it('allows team members to use incident and alert operations but blocks admin only tools', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($user, [
        'role' => TeamRole::Member->value,
    ]);

    expect($user->switchTeam($team))->toBeTrue();

    $this->actingAs($user)
        ->get(route('security-alerts.index'))
        ->assertOk();

    $this->get(route('sql-query.index'))
        ->assertForbidden();

    $this->get(route('security-policies.index'))
        ->assertForbidden();
});

it('allows team admins to access admin only security tools', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($user, [
        'role' => TeamRole::Admin->value,
    ]);

    expect($user->switchTeam($team))->toBeTrue();

    $this->actingAs($user)
        ->get(route('sql-query.index'))
        ->assertOk();
});
