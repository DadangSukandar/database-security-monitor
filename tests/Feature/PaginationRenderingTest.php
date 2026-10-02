<?php

use App\Models\SecurityAlert;
use App\Models\Team;
use App\Models\User;

it('renders compact Bootstrap pagination instead of unbounded SVG arrows', function () {
    $user =
        User::factory()->create();

    $team =
        Team::factory()->create();

    $team->members()->attach(
        $user->id,
        [
            'role' => 'admin',
        ]
    );

    expect(
        $user->switchTeam($team)
    )->toBeTrue();

    foreach (range(1, 16) as $number) {
        $alert = new SecurityAlert([
            'alert_type' => 'TEST_ALERT',

            'severity' => 'LOW',

            'title' => 'Pagination alert '.$number,

            'status' => 'OPEN',

            'detected_at' => now(),
        ]);

        $alert->team_id =
            $team->id;

        $alert->save();
    }

    $this->actingAs($user);

    $this->get(
        route('security-alerts.index')
    )
        ->assertOk()
        ->assertSee(
            'class="pagination"',
            false
        )
        ->assertSee(
            'page-link',
            false
        )
        ->assertDontSee(
            '<svg',
            false
        );
});
