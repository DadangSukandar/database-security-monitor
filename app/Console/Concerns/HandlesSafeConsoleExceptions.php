<?php

namespace App\Console\Concerns;

use Throwable;

trait HandlesSafeConsoleExceptions
{
    protected function reportConsoleException(Throwable $exception): void
    {
        report($exception);
    }

    protected function safeConsoleExceptionMessage(): string
    {
        return 'Detail teknis telah dicatat di log aplikasi.';
    }
}
