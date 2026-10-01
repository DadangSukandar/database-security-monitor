<?php

namespace Tests\Feature;

use App\Models\DatabaseConnection;
use App\Models\DiscoveredColumn;
use App\Models\DiscoveredDatabase;
use App\Models\DiscoveredTable;
use App\Models\SensitiveDataFinding;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SensitiveDataTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team =
            Team::factory()->create();
    }

    public function test_sensitive_data_index_only_contains_current_team_findings_and_statistics(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam =
            Team::factory()->create();

        $teamAColumn =
            $this->createColumnForTeam(
                $this->team,
                'email'
            );

        $teamBColumn =
            $this->createColumnForTeam(
                $otherTeam,
                'credit_card_number'
            );

        $teamAFinding =
            SensitiveDataFinding::query()
                ->create([
                    'discovered_column_id' => $teamAColumn->id,

                    'category' => 'PII',

                    'risk_level' => 'HIGH',

                    'rule_name' => 'TEAM_A_FINDING',

                    'description' => 'Team A sensitive data.',
                ]);

        $teamBFinding =
            SensitiveDataFinding::query()
                ->create([
                    'discovered_column_id' => $teamBColumn->id,

                    'category' => 'FINANCIAL',

                    'risk_level' => 'CRITICAL',

                    'rule_name' => 'TEAM_B_SECRET',

                    'description' => 'Team B sensitive data.',
                ]);

        $response =
            $this->get(
                route(
                    'sensitive-data.index'
                )
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'total',
                1
            )
            ->assertViewHas(
                'critical',
                0
            )
            ->assertViewHas(
                'high',
                1
            )
            ->assertViewHas(
                'medium',
                0
            )
            ->assertViewHas(
                'low',
                0
            )
            ->assertViewHas(
                'findings',
                function ($findings) use (
                    $teamAFinding,
                    $teamBFinding
                ): bool {
                    $items =
                        method_exists(
                            $findings,
                            'items'
                        )
                            ? collect(
                                $findings->items()
                            )
                            : collect(
                                $findings
                            );

                    return $items->contains(
                        'id',
                        $teamAFinding->id
                    )
                        && ! $items->contains(
                            'id',
                            $teamBFinding->id
                        );
                }
            );
    }

    public function test_sensitive_data_scan_only_changes_current_team_columns(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam =
            Team::factory()->create();

        $teamAColumn =
            $this->createColumnForTeam(
                $this->team,
                'password'
            );

        $teamBColumn =
            $this->createColumnForTeam(
                $otherTeam,
                'password'
            );

        SensitiveDataFinding::query()
            ->create([
                'discovered_column_id' => $teamAColumn->id,

                'category' => 'OLD',

                'risk_level' => 'LOW',

                'rule_name' => 'OLD_TEAM_A',

                'description' => 'Old Team A finding.',
            ]);

        $teamBFinding =
            SensitiveDataFinding::query()
                ->create([
                    'discovered_column_id' => $teamBColumn->id,

                    'category' => 'FOREIGN',

                    'risk_level' => 'CRITICAL',

                    'rule_name' => 'TEAM_B_MUST_REMAIN',

                    'description' => 'Team B finding.',
                ]);

        $this->post(
            route(
                'sensitive-data.scan'
            )
        )
            ->assertRedirect(
                route(
                    'sensitive-data.index'
                )
            );

        $this->assertDatabaseHas(
            'sensitive_data_findings',
            [
                'discovered_column_id' => $teamAColumn->id,

                'rule_name' => 'PASSWORD_FIELD',
            ]
        );

        $this->assertDatabaseMissing(
            'sensitive_data_findings',
            [
                'discovered_column_id' => $teamAColumn->id,

                'rule_name' => 'OLD_TEAM_A',
            ]
        );

        $this->assertDatabaseHas(
            'sensitive_data_findings',
            [
                'id' => $teamBFinding->id,

                'discovered_column_id' => $teamBColumn->id,

                'rule_name' => 'TEAM_B_MUST_REMAIN',
            ]
        );

        $this->assertDatabaseCountForColumn(
            $teamBColumn,
            1
        );
    }

    private function assertDatabaseCountForColumn(
        DiscoveredColumn $column,
        int $expected
    ): void {
        $this->assertSame(
            $expected,
            SensitiveDataFinding::query()
                ->where(
                    'discovered_column_id',
                    $column->id
                )
                ->count()
        );
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

        $user->refresh();

        $this->actingAs(
            $user
        );

        return $user;
    }

    private function createColumnForTeam(
        Team $team,
        string $columnName
    ): DiscoveredColumn {
        $connection =
            new DatabaseConnection([
                'name' => 'Connection '.$team->id,

                'driver' => 'mysql',

                'host' => '127.0.0.1',

                'port' => 3306,

                'database' => 'database_'.$team->id,

                'username' => 'test_user',

                'password' => null,

                'is_active' => true,
            ]);

        $connection->team_id =
            $team->id;

        $connection->save();

        $database =
            DiscoveredDatabase::query()
                ->create([
                    'database_connection_id' => $connection->id,

                    'name' => 'database_'.$team->id,

                    'engine' => 'mysql',

                    'version' => '8.0',
                ]);

        $table =
            DiscoveredTable::query()
                ->create([
                    'discovered_database_id' => $database->id,

                    'schema_name' => $database->name,

                    'name' => 'users_'.$team->id,

                    'type' => 'BASE TABLE',

                    'estimated_rows' => 10,
                ]);

        return DiscoveredColumn::query()
            ->create([
                'discovered_table_id' => $table->id,

                'name' => $columnName,

                'data_type' => 'varchar',

                'column_type' => 'varchar(255)',

                'is_nullable' => true,

                'default_value' => null,

                'is_primary' => false,
            ]);
    }
}
