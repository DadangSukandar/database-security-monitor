<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ConsoleExceptionDisclosureGuardTest extends TestCase
{
    public function test_console_commands_do_not_print_raw_exception_messages(): void
    {
        $files = File::allFiles(
            app_path('Console/Commands')
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = File::get(
                $file->getRealPath()
            );

            $this->assertDoesNotMatchRegularExpression(
                '/->getMessage\s*\(/',
                $contents,
                'Raw exception message ditemukan di '.$file->getFilename()
            );
        }
    }
}
