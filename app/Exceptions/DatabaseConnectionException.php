<?php

namespace App\Exceptions;

use App\Enums\DatabaseConnectionFailureType;
use RuntimeException;
use Throwable;

class DatabaseConnectionException extends RuntimeException
{
    public function __construct(
        public readonly DatabaseConnectionFailureType $failureType,
        Throwable $originalException
    ) {
        /*
        |--------------------------------------------------------------------------
        | Credential-Safe Exception Boundary
        |--------------------------------------------------------------------------
        |
        | $originalException hanya diterima agar caller tidak perlu berubah
        | dan classifier dapat bekerja sebelum wrapper dibuat.
        |
        | Exception mentah TIDAK dipasang sebagai previous exception.
        |
        | Alasannya: PDO / database driver exception dapat membawa host,
        | username, database, DSN, atau detail autentikasi pada message.
        | Jika dipasang sebagai previous, logger dapat mencetak seluruh
        | exception chain.
        |
        */

        unset($originalException);

        parent::__construct(
            'Database monitoring connection failed.'
        );
    }

    /**
     * Safe structured context yang boleh dipakai untuk logging.
     *
     * @return array<string, string>
     */
    public function context(): array
    {
        return [
            'database_connection_failure_type' => $this->failureType->value,
        ];
    }
}
