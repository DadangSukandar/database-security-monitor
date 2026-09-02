<?php

use App\Services\ReadOnlySqlGuard;

it('allows safe read only statements', function (string $sql, bool $metadata) {
    $guard = app(ReadOnlySqlGuard::class);

    expect($guard->validationError($sql, $metadata))->toBeNull();
})->with([
    ['SELECT id, name FROM users WHERE id = 1', false],
    ['WITH active AS (SELECT id FROM users) SELECT * FROM active', false],
    ['SHOW TABLES', true],
    ['DESCRIBE users', true],
    ['EXPLAIN SELECT * FROM users', true],
]);

it('rejects statements with write or locking behavior', function (string $sql, bool $metadata = true) {
    $guard = app(ReadOnlySqlGuard::class);

    expect($guard->validationError($sql, $metadata))->not->toBeNull();
})->with([
    ['SELECT * FROM users; DELETE FROM users'],
    ['WITH changed AS (UPDATE users SET name = \'x\' RETURNING *) SELECT * FROM changed'],
    ['SELECT * INTO archived_users FROM users'],
    ['SELECT * FROM users FOR UPDATE'],
    ['SELECT GET_LOCK(\'guardium\', 10)'],
    ['SELECT SLEEP(10)'],
    ['SELECT * FROM users INTO OUTFILE \'/tmp/users.txt\''],
    ['EXPLAIN DELETE FROM users'],
]);

it('does not treat blocked words inside string literals as SQL commands', function () {
    $guard = app(ReadOnlySqlGuard::class);

    expect(
        $guard->validationError("SELECT 'DELETE FROM users' AS example")
    )->toBeNull();
});
