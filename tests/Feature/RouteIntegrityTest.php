<?php

use Illuminate\Support\Facades\Route;

it('only exposes implemented database connection resource actions', function () {
    expect(Route::has('database-connections.index'))->toBeTrue()
        ->and(Route::has('database-connections.create'))->toBeTrue()
        ->and(Route::has('database-connections.store'))->toBeTrue()
        ->and(Route::has('database-connections.show'))->toBeTrue()
        ->and(Route::has('database-connections.destroy'))->toBeTrue()
        ->and(Route::has('database-connections.edit'))->toBeFalse()
        ->and(Route::has('database-connections.update'))->toBeFalse();
});

it('constrains model route parameters to numeric identifiers', function () {
    $this->get('/security-alerts/not-an-id')->assertNotFound();
    $this->get('/security-findings/not-an-id')->assertNotFound();
    $this->get('/vulnerability-assessments/not-an-id')->assertNotFound();
});
