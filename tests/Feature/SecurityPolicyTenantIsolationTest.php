<?php

namespace Tests\Feature;

use App\Models\SecurityPolicy;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityPolicyTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team =
            Team::factory()->create();
    }

    public function test_security_policy_index_only_contains_current_team_data_and_statistics(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam =
            Team::factory()->create();

        $teamAActive =
            $this->createPolicy(
                $this->team,
                [
                    'name' => 'Team A Critical Policy',

                    'code' => 'TEAM_A_CRITICAL',

                    'severity' => 'CRITICAL',

                    'is_active' => true,
                ]
            );

        $teamAInactive =
            $this->createPolicy(
                $this->team,
                [
                    'name' => 'Team A Inactive Policy',

                    'code' => 'TEAM_A_INACTIVE',

                    'severity' => 'LOW',

                    'is_active' => false,
                ]
            );

        $teamBPolicy =
            $this->createPolicy(
                $otherTeam,
                [
                    'name' => 'Team B Secret Policy',

                    'code' => 'TEAM_B_SECRET',

                    'severity' => 'CRITICAL',

                    'is_active' => true,
                ]
            );

        $response =
            $this->get(
                route(
                    'security-policies.index'
                )
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'totalPolicies',
                2
            )
            ->assertViewHas(
                'activePolicies',
                1
            )
            ->assertViewHas(
                'inactivePolicies',
                1
            )
            ->assertViewHas(
                'criticalPolicies',
                1
            )
            ->assertViewHas(
                'policies',
                function ($policies) use (
                    $teamAActive,
                    $teamAInactive,
                    $teamBPolicy
                ): bool {
                    $items =
                        method_exists(
                            $policies,
                            'items'
                        )
                            ? collect(
                                $policies->items()
                            )
                            : collect($policies);

                    return $items->contains(
                        'id',
                        $teamAActive->id
                    )
                        && $items->contains(
                            'id',
                            $teamAInactive->id
                        )
                        && ! $items->contains(
                            'id',
                            $teamBPolicy->id
                        );
                }
            )
            ->assertSee(
                'Team A Critical Policy'
            )
            ->assertDontSee(
                'Team B Secret Policy'
            );
    }

    public function test_store_assigns_current_team_and_allows_same_code_used_by_other_team(): void
    {
        $this->actingAsTeamAdmin();

        $otherTeam =
            Team::factory()->create();

        $this->createPolicy(
            $otherTeam,
            [
                'code' => 'SHARED_POLICY_CODE',
            ]
        );

        $response =
            $this->post(
                route(
                    'security-policies.store'
                ),
                [
                    'name' => 'Current Team Policy',

                    'code' => 'shared_policy_code',

                    'rule_type' => 'ACCESS_CONTROL',

                    'severity' => 'HIGH',

                    'priority' => 100,

                    'conditions' => null,

                    'is_active' => true,
                ]
            );

        $response->assertRedirect(
            route(
                'security-policies.index'
            )
        );

        $this->assertDatabaseHas(
            'security_policies',
            [
                'team_id' => $this->team->id,

                'code' => 'SHARED_POLICY_CODE',

                'name' => 'Current Team Policy',
            ]
        );
    }

    public function test_same_team_cannot_create_duplicate_policy_code(): void
    {
        $this->actingAsTeamAdmin();

        $this->createPolicy(
            $this->team,
            [
                'code' => 'DUPLICATE_CODE',
            ]
        );

        $response =
            $this->from(
                route(
                    'security-policies.create'
                )
            )
                ->post(
                    route(
                        'security-policies.store'
                    ),
                    [
                        'name' => 'Duplicate Policy',

                        'code' => 'duplicate_code',

                        'rule_type' => 'ACCESS_CONTROL',

                        'severity' => 'HIGH',

                        'priority' => 100,

                        'conditions' => null,

                        'is_active' => true,
                    ]
                );

        $response
            ->assertRedirect(
                route(
                    'security-policies.create'
                )
            )
            ->assertSessionHasErrors(
                'code'
            );
    }

    public function test_cross_team_security_policy_edit_returns_not_found(): void
    {
        $this->actingAsTeamAdmin();

        $foreign =
            $this->foreignPolicy();

        $this->get(
            route(
                'security-policies.edit',
                $foreign
            )
        )
            ->assertNotFound();
    }

    public function test_cross_team_security_policy_update_returns_not_found(): void
    {
        $this->actingAsTeamAdmin();

        $foreign =
            $this->foreignPolicy();

        $this->put(
            route(
                'security-policies.update',
                $foreign
            ),
            [
                'name' => 'Should Not Change',

                'code' => $foreign->code,

                'rule_type' => 'ACCESS_CONTROL',

                'severity' => 'LOW',

                'priority' => 1,

                'conditions' => null,

                'is_active' => true,
            ]
        )
            ->assertNotFound();

        $this->assertDatabaseMissing(
            'security_policies',
            [
                'id' => $foreign->id,

                'name' => 'Should Not Change',
            ]
        );
    }

    public function test_cross_team_security_policy_destroy_returns_not_found(): void
    {
        $this->actingAsTeamAdmin();

        $foreign =
            $this->foreignPolicy();

        $this->delete(
            route(
                'security-policies.destroy',
                $foreign
            )
        )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'security_policies',
            [
                'id' => $foreign->id,
            ]
        );
    }

    public function test_cross_team_security_policy_toggle_returns_not_found(): void
    {
        $this->actingAsTeamAdmin();

        $foreign =
            $this->foreignPolicy();

        $original =
            $foreign->is_active;

        $this->post(
            route(
                'security-policies.toggle',
                $foreign
            )
        )
            ->assertNotFound();

        $this->assertSame(
            $original,
            $foreign->fresh()->is_active
        );
    }

    private function foreignPolicy(): SecurityPolicy
    {
        return $this->createPolicy(
            Team::factory()->create(),
            [
                'name' => 'Foreign Policy',

                'code' => 'FOREIGN_POLICY_'.uniqid(),

                'is_active' => true,
            ]
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

        $user->unsetRelation(
            'currentTeam'
        );

        $user->refresh();

        $this->actingAs(
            $user
        );

        return $user;
    }

    private function createPolicy(
        Team $team,
        array $attributes = []
    ): SecurityPolicy {
        $policy =
            new SecurityPolicy(
                array_merge([
                    'name' => 'Test Policy',

                    'code' => 'POLICY_'.uniqid(),

                    'rule_type' => 'ACCESS_CONTROL',

                    'severity' => 'HIGH',

                    'conditions' => null,

                    'priority' => 100,

                    'is_active' => true,
                ], $attributes)
            );

        $policy->team_id =
            $team->id;

        $policy->save();

        return $policy;
    }
}
