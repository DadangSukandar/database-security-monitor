<?php

use App\Services\DatabaseActivityQuerySanitizer;

function activityQuerySanitizer(): DatabaseActivityQuerySanitizer
{
    return app(
        DatabaseActivityQuerySanitizer::class
    );
}

it('redacts quoted string literals', function () {
    $query =
        'SELECT * FROM users '.
        "WHERE email = 'dadang@example.com' ".
        "AND token = 'SuperSecretToken'";

    $sanitized =
        activityQuerySanitizer()
            ->sanitize(
                $query
            );

    expect(
        $sanitized
    )
        ->toContain(
            "email = '?'"
        )
        ->toContain(
            "token = '?'"
        )
        ->not->toContain(
            'dadang@example.com'
        )
        ->not->toContain(
            'SuperSecretToken'
        );
});

it('redacts numeric literals without destroying identifiers', function () {
    $query =
        'SELECT column1 '.
        'FROM table2026 '.
        'WHERE customer_id = 12345 '.
        'AND balance = 10.50';

    $sanitized =
        activityQuerySanitizer()
            ->sanitize(
                $query
            );

    expect(
        $sanitized
    )
        ->toContain(
            'column1'
        )
        ->toContain(
            'table2026'
        )
        ->toContain(
            'customer_id = ?'
        )
        ->toContain(
            'balance = ?'
        )
        ->not->toContain(
            '12345'
        )
        ->not->toContain(
            '10.50'
        );
});

it('redacts escaped sql string literals', function () {
    $query =
        'SELECT * FROM customers '.
        "WHERE name = 'O''Brien'";

    $sanitized =
        activityQuerySanitizer()
            ->sanitize(
                $query
            );

    expect(
        $sanitized
    )
        ->toContain(
            "name = '?'"
        )
        ->not->toContain(
            "O''Brien"
        );
});

it('redacts secrets stored inside sql comments', function () {
    $query =
        'SELECT * FROM users '.
        '/* token=SecretBlockToken */ '.
        'WHERE id = 99 '.
        '-- password=SecretLinePassword';

    $sanitized =
        activityQuerySanitizer()
            ->sanitize(
                $query
            );

    expect(
        $sanitized
    )
        ->not->toContain(
            'SecretBlockToken'
        )
        ->not->toContain(
            'SecretLinePassword'
        )
        ->toContain(
            '/* redacted */'
        )
        ->toContain(
            '-- redacted'
        );
});

it('redacts postgres dollar quoted literals', function () {
    $query =
        'SELECT $tag$'.
        'SensitivePostgresValue'.
        '$tag$';

    $sanitized =
        activityQuerySanitizer()
            ->sanitize(
                $query
            );

    expect(
        $sanitized
    )
        ->toContain(
            '$tag$?$tag$'
        )
        ->not->toContain(
            'SensitivePostgresValue'
        );
});

it('preserves sql structure and parameter placeholders', function () {
    $query =
        'SELECT id, email '.
        'FROM users '.
        'WHERE id = ? '.
        'AND email = :email';

    $sanitized =
        activityQuerySanitizer()
            ->sanitize(
                $query
            );

    expect(
        $sanitized
    )->toBe(
        $query
    );
});

it('redacts mysql double quoted string literals', function () {
    $query =
        'SELECT * FROM users '.
        'WHERE token = "MysqlSecretValue"';

    $sanitized =
        activityQuerySanitizer()
            ->sanitize(
                $query,
                'mysql'
            );

    expect(
        $sanitized
    )
        ->toContain(
            'token = "?"'
        )
        ->not->toContain(
            'MysqlSecretValue'
        );
});

it('preserves postgres double quoted identifiers', function () {
    $query =
        'SELECT "UserEmail" '.
        'FROM "CustomerTable" '.
        'WHERE id = 12345';

    $sanitized =
        activityQuerySanitizer()
            ->sanitize(
                $query,
                'pgsql'
            );

    expect(
        $sanitized
    )
        ->toContain(
            '"UserEmail"'
        )
        ->toContain(
            '"CustomerTable"'
        )
        ->toContain(
            'id = ?'
        )
        ->not->toContain(
            '12345'
        );
});
