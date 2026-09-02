<?php

namespace App\Http\Controllers;

use Throwable;

abstract class Controller
{
    protected function safeExceptionDetail(Throwable $exception): string
    {
        report($exception);

        return 'Detail teknis telah dicatat di log aplikasi.';
    }
}
