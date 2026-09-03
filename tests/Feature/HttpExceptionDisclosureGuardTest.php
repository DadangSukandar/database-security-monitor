<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class HttpExceptionDisclosureGuardTest extends TestCase
{
    public function test_http_controllers_do_not_expose_raw_exception_messages(): void
    {
        $controllerPath = app_path('Http/Controllers');

        $violations = [];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($controllerPath)
        );

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents(
                $file->getPathname()
            );

            if ($contents === false) {
                continue;
            }

            if (preg_match('/->getMessage\s*\(/', $contents)) {
                $violations[] = $file->getPathname();
            }
        }

        $this->assertSame(
            [],
            $violations,
            "HTTP controller tidak boleh mengekspos raw exception message.\n".
            "Gunakan safeExceptionDetail() atau pesan aman.\n\n".
            implode("\n", $violations)
        );
    }

    public function test_http_controllers_do_not_contain_debug_dump_calls(): void
    {
        $controllerPath = app_path('Http/Controllers');

        $violations = [];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($controllerPath)
        );

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents(
                $file->getPathname()
            );

            if ($contents === false) {
                continue;
            }

            if (
                preg_match(
                    '/\b(?:dd|dump|var_dump)\s*\(/',
                    $contents
                )
            ) {
                $violations[] = $file->getPathname();
            }
        }

        $this->assertSame(
            [],
            $violations,
            "HTTP controller tidak boleh berisi debug dump.\n\n".
            implode("\n", $violations)
        );
    }
}
