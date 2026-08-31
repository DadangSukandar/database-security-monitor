<?php

use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    TeamInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily()->description('Delete expired team invitations');

Schedule::command('security:auto-scan')
    ->everyFifteenMinutes();

Schedule::command('security:escalate-alerts')
    ->everyFiveMinutes()
    ->withoutOverlapping();
