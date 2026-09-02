<?php

use App\Models\SecurityAlert;
use App\Models\User;

it('renders compact Bootstrap pagination instead of unbounded SVG arrows', function () {
    foreach (range(1, 16) as $number) {
        SecurityAlert::query()->create([
            'alert_type' => 'TEST_ALERT',
            'severity' => 'LOW',
            'title' => 'Pagination alert '.$number,
            'status' => 'OPEN',
            'detected_at' => now(),
        ]);
    }

    $this->actingAs(
        User::factory()->create()
    );

    $this->get(route('security-alerts.index'))
        ->assertOk()
        ->assertSee('class="pagination"', false)
        ->assertSee('page-link', false)
        ->assertDontSee('<svg', false);
});
