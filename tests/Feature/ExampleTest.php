<?php

test('home redirects unauthenticated users', function () {
    $this
        ->get(route('home'))
        ->assertRedirect();
});
