<?php

namespace Tests\Feature;

use App\Models\DatabaseConnection;
use App\Models\DiscoveredColumn;
use App\Models\DiscoveredDatabase;
use App\Models\DiscoveredTable;
use App\Models\Team;
use App\Models\User;
use App\Services\DatabaseDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseDiscoveryTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team =
            Team::factory()->create();
    }

    public function test_database_discovery_index_only_contains_current_team_data_and_statistics(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam =
            Team::factory()->create();

        $teamAConnection =
            $this->createConnection(
                $this->team,
                'Team A Discovery',
                'team_a_discovery'
            );

        $teamBConnection =
            $this->createConnection(
                $otherTeam,
                'Team B Discovery',
                'team_b_discovery'
            );

        $teamADatabase =
            $this->createDiscoveredDatabase(
                $teamAConnection,
                'team_a_discovered'
            );

        $teamBDatabase =
            $this->createDiscoveredDatabase(
                $teamBConnection,
                'team_b_discovered'
            );

        $teamATable =
            $this->createDiscoveredTable(
                $teamADatabase,
                'team_a_users'
            );

        $teamBTable =
            $this->createDiscoveredTable(
                $teamBDatabase,
                'team_b_secret'
            );

        $this->createDiscoveredColumn(
            $teamATable,
            'email'
        );

        $this->createDiscoveredColumn(
            $teamATable,
            'password'
        );

        $this->createDiscoveredColumn(
            $teamBTable,
            'secret_token'
        );

        $response =
            $this->get(
                route(
                    'database-discovery.index'
                )
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'totalDatabases',
                1
            )
            ->assertViewHas(
                'totalTables',
                1
            )
            ->assertViewHas(
                'totalColumns',
                2
            )
            ->assertViewHas(
                'databases',
                function ($databases) use (
                    $teamADatabase,
                    $teamBDatabase
                ): bool {
                    return $databases->contains(
                        'id',
                        $teamADatabase->id
                    )
                        && ! $databases->contains(
                            'id',
                            $teamBDatabase->id
                        );
                }
            )
            ->assertViewHas(
                'connections',
                function ($connections) use (
                    $teamAConnection,
                    $teamBConnection
                ): bool {
                    return $connections->contains(
                        'id',
                        $teamAConnection->id
                    )
                        && ! $connections->contains(
                            'id',
                            $teamBConnection->id
                        );
                }
            )
            ->assertSee(
                'team_a_discovered'
            )
            ->assertDontSee(
                'team_b_discovered'
            );
    }

    public function test_cross_team_database_discovery_scan_is_rejected_before_service(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam =
            Team::factory()->create();

        $foreignConnection =
            $this->createConnection(
                $otherTeam,
                'Foreign Discovery',
                'foreign_discovery'
            );

        $service =
            $this->mock(
                DatabaseDiscoveryService::class
            );

        $service->shouldNotReceive(
            'scan'
        );

        $this->post(
            route(
                'database-discovery.scan',
                $foreignConnection
            )
        )
            ->assertNotFound();
    }

    public function test_current_team_can_view_own_discovered_database(): void
    {
        $this->actingAsTeamAdmin();

        $connection =
            $this->createConnection(
                $this->team,
                'Owned Discovery',
                'owned_discovery'
            );

        $database =
            $this->createDiscoveredDatabase(
                $connection,
                'owned_database'
            );

        $this->get(
            route(
                'database-discovery.show',
                $database
            )
        )
            ->assertOk();
    }

    public function test_cross_team_discovered_database_returns_not_found(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam =
            Team::factory()->create();

        $connection =
            $this->createConnection(
                $otherTeam,
                'Foreign Discovery',
                'foreign_database'
            );

        $database =
            $this->createDiscoveredDatabase(
                $connection,
                'foreign_discovered_database'
            );

        $this->get(
            route(
                'database-discovery.show',
                $database
            )
        )
            ->assertNotFound();
    }

    public function test_cross_team_discovered_table_returns_not_found(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam =
            Team::factory()->create();

        $connection =
            $this->createConnection(
                $otherTeam,
                'Foreign Table Connection',
                'foreign_table_database'
            );

        $database =
            $this->createDiscoveredDatabase(
                $connection,
                'foreign_database'
            );

        $table =
            $this->createDiscoveredTable(
                $database,
                'foreign_secret_table'
            );

        $this->get(
            route(
                'database-discovery.table',
                $table
            )
        )
            ->assertNotFound();
    }

    private function actingAsTeamAdmin(): User
    {
        $user =
            User::factory()->create();

        $this->team->members()->attach(
            $user->id,
            [
                'role' => 'admin',
            ]
        );

        $user->forceFill([
            'current_team_id' => $this->team->id,
        ])->save();

        $user->unsetRelation(
            'currentTeam'
        );

        $user->refresh();

        $this->actingAs(
            $user
        );

        return $user;
    }

    private function createConnection(
        Team $team,
        string $name,
        string $database
    ): DatabaseConnection {
        $connection =
            new DatabaseConnection([
                'name' => $name,

                'driver' => 'mysql',

                'host' => '127.0.0.1',

                'port' => 3306,

                'database' => $database,

                'username' => 'test_user',

                'password' => null,

                'is_active' => true,
            ]);

        $connection->team_id =
            $team->id;

        $connection->save();

        return $connection;
    }

    private function createDiscoveredDatabase(
        DatabaseConnection $connection,
        string $name
    ): DiscoveredDatabase {
        return DiscoveredDatabase::query()
            ->create([
                'database_connection_id' => $connection->id,

                'name' => $name,

                'engine' => 'mysql',

                'version' => '8.0',
            ]);
    }

    private function createDiscoveredTable(
        DiscoveredDatabase $database,
        string $name
    ): DiscoveredTable {
        return DiscoveredTable::query()
            ->create([
                'discovered_database_id' => $database->id,

                'schema_name' => $database->name,

                'name' => $name,

                'type' => 'BASE TABLE',

                'estimated_rows' => 10,
            ]);
    }

    private function createDiscoveredColumn(
        DiscoveredTable $table,
        string $name
    ): DiscoveredColumn {
        return DiscoveredColumn::query()
            ->create([
                'discovered_table_id' => $table->id,

                'name' => $name,

                'data_type' => 'varchar',

                'column_type' => 'varchar(255)',

                'is_nullable' => true,

                'default_value' => null,

                'is_primary' => false,
            ]);
    }
}
