<?php

namespace App\Services;

use App\Enums\DatabaseConnectionFailureType;
use Throwable;

class DatabaseConnectionFailureClassifier
{
    public function classify(Throwable $exception): DatabaseConnectionFailureType
    {
        $message = strtolower($exception->getMessage());

        if ($this->containsAny($message, [
            'timed out',
            'timeout',
            'connection timeout',
            'operation timed out',
        ])) {
            return DatabaseConnectionFailureType::TIMEOUT;
        }

        if ($this->containsAny($message, [
            'access denied',
            'authentication failed',
            'password authentication failed',
            'invalid authorization specification',
            'login failed',
        ])) {
            return DatabaseConnectionFailureType::AUTHENTICATION_FAILED;
        }

        if ($this->containsAny($message, [
            'connection refused',
            'no route to host',
            'network is unreachable',
            'could not connect to server',
            'could not translate host name',
            'name or service not known',
            'getaddrinfo',
            'php_network_getaddresses',
        ])) {
            return DatabaseConnectionFailureType::HOST_UNREACHABLE;
        }

        if ($this->containsAny($message, [
            'unknown database',
            'database does not exist',
            'does not exist',
            'cannot open database',
        ])) {
            return DatabaseConnectionFailureType::DATABASE_UNAVAILABLE;
        }

        if ($this->containsAny($message, [
            'ssl',
            'tls',
            'certificate verify failed',
            'certificate verification failed',
            'certificate has expired',
        ])) {
            return DatabaseConnectionFailureType::TLS_ERROR;
        }

        if ($this->containsAny($message, [
            'transaction read-only',
            'transaction_read_only',
            'default_transaction_read_only',
            'read only session',
            'read-only session',
            'read only transaction',
            'read-only transaction',
        ])) {
            return DatabaseConnectionFailureType::READ_ONLY_SETUP_FAILED;
        }

        return DatabaseConnectionFailureType::UNKNOWN;
    }

    private function containsAny(string $message, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}
