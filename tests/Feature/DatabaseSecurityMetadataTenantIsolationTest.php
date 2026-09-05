<?php

namespace Tests\Feature;

use App\Models\DatabaseConnection;
use App\Models\DatabasePrivilege;
use App\Models\DatabaseUser;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSecurityMetadataTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function prepareUserAndTeams(): array
    {
        $user = User::factory()->create();

        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $teamA->members()->attach($user->id, [
            'role' => 'admin',
        ]);

        $user->forceFill([
            'current_team_id' => $teamA->id,
        ])->save();

        $user->unsetRelation('currentTeam');
        $user->refresh();

        return [$user, $teamA, $teamB];
    }

    private function createConnectionForTeam(
        Team $team,
        string $name
    ): DatabaseConnection {
        $connection = new DatabaseConnection([
            'name' => $name,
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'test_db',
            'username' => 'test_user',
            'password' => 'secret',
            'is_active' => true,
        ]);

        $connection->team_id = $team->id;

        $connection->save();

        return $connection;
    }

    public function test_database_user_index_only_shows_current_team_data(): void
    {
        [$user, $teamA, $teamB] = $this->prepareUserAndTeams();

        $connectionA = $this->createConnectionForTeam(
            $teamA,
            'Team A Connection'
        );

        $connectionB = $this->createConnectionForTeam(
            $teamB,
            'Team B Connection'
        );

        DatabaseUser::query()->create([
            'database_connection_id' => $connectionA->id,
            'username' => 'team_a_user',
            'host' => 'localhost',
            'can_login' => true,
            'is_superuser' => false,
            'risk_level' => 'LOW',
        ]);

        DatabaseUser::query()->create([
            'database_connection_id' => $connectionB->id,
            'username' => 'team_b_user',
            'host' => 'localhost',
            'can_login' => true,
            'is_superuser' => false,
            'risk_level' => 'LOW',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('database-users.index'));

        $response
            ->assertOk()
            ->assertSee('team_a_user')
            ->assertDontSee('team_b_user')
            ->assertSee('Team A Connection')
            ->assertDontSee('Team B Connection');
    }

    public function test_database_privilege_index_only_shows_current_team_data(): void
    {
        [$user, $teamA, $teamB] = $this->prepareUserAndTeams();

        $connectionA = $this->createConnectionForTeam(
            $teamA,
            'Team A Privilege Connection'
        );

        $connectionB = $this->createConnectionForTeam(
            $teamB,
            'Team B Privilege Connection'
        );

        DatabasePrivilege::query()->create([
            'database_connection_id' => $connectionA->id,
            'username' => 'team_a_priv_user',
            'host' => 'localhost',
            'database_name' => '*',
            'table_name' => '*',
            'privilege' => 'SELECT',
            'is_grantable' => false,
            'risk_level' => 'LOW',
        ]);

        DatabasePrivilege::query()->create([
            'database_connection_id' => $connectionB->id,
            'username' => 'team_b_priv_user',
            'host' => 'localhost',
            'database_name' => '*',
            'table_name' => '*',
            'privilege' => 'SELECT',
            'is_grantable' => false,
            'risk_level' => 'LOW',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('database-privileges.index'));

        $response
            ->assertOk()
            ->assertSee('team_a_priv_user')
            ->assertDontSee('team_b_priv_user')
            ->assertSee('Team A Privilege Connection')
            ->assertDontSee('Team B Privilege Connection');
    }

    public function test_cross_team_database_user_scan_is_not_accessible(): void
    {
        [$user, , $teamB] = $this->prepareUserAndTeams();

        $connection = $this->createConnectionForTeam(
            $teamB,
            'Foreign User Scan Connection'
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route(
                    'database-users.scan',
                    $connection
                )
            );

        $response->assertNotFound();
    }

    public function test_cross_team_database_privilege_scan_is_not_accessible(): void
    {
        [$user, , $teamB] = $this->prepareUserAndTeams();

        $connection = $this->createConnectionForTeam(
            $teamB,
            'Foreign Privilege Scan Connection'
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route(
                    'database-privileges.scan',
                    $connection
                )
            );

        $response->assertNotFound();
    }
}
