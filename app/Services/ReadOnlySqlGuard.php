<?php

namespace App\Services;

final class ReadOnlySqlGuard
{
    /**
     * Validate a SQL statement intended for the read-only query consoles.
     */
    public function validationError(string $sql, bool $allowMetadataStatements = false): ?string
    {
        $sql = trim($sql);

        if ($sql === '') {
            return 'Query tidak boleh kosong.';
        }

        if (str_contains($sql, "\0")) {
            return 'Query mengandung karakter yang tidak valid.';
        }

        $inspectable = trim($this->inspectableSql($sql));
        $inspectable = rtrim($inspectable);

        if (str_ends_with($inspectable, ';')) {
            $inspectable = rtrim(substr($inspectable, 0, -1));
        }

        if ($inspectable === '') {
            return 'Query tidak boleh kosong.';
        }

        if (str_contains($inspectable, ';')) {
            return 'Multiple SQL statement tidak diperbolehkan.';
        }

        $firstKeyword = $this->firstKeyword($inspectable);
        $allowed = $allowMetadataStatements
            ? ['SELECT', 'WITH', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN']
            : ['SELECT', 'WITH'];

        if ($firstKeyword === null || ! in_array($firstKeyword, $allowed, true)) {
            return $allowMetadataStatements
                ? 'SQL Console hanya mengizinkan SELECT, WITH, SHOW, DESCRIBE, DESC, atau EXPLAIN.'
                : 'Query Console hanya mengizinkan SELECT atau WITH.';
        }

        foreach ($this->blockedKeywords() as $keyword) {
            if (preg_match('/\b'.preg_quote($keyword, '/').'\b/i', $inspectable) === 1) {
                return "Keyword {$keyword} tidak diperbolehkan.";
            }
        }

        foreach ($this->blockedPatterns() as $pattern => $message) {
            if (preg_match($pattern, $inspectable) === 1) {
                return $message;
            }
        }

        if ($firstKeyword === 'EXPLAIN') {
            $explainedKeyword = $this->keywordAfterExplain($inspectable);

            if ($explainedKeyword !== null && ! in_array($explainedKeyword, ['SELECT', 'WITH'], true)) {
                return 'EXPLAIN hanya boleh digunakan untuk SELECT atau WITH.';
            }
        }

        return null;
    }

    /**
     * Replace quoted values and comments while preserving SQL structure.
     * This prevents keywords inside literals/comments from affecting policy checks.
     */
    private function inspectableSql(string $sql): string
    {
        $output = '';
        $length = strlen($sql);
        $state = 'normal';
        $dollarTerminator = null;

        for ($index = 0; $index < $length; $index++) {
            $char = $sql[$index];
            $next = $index + 1 < $length ? $sql[$index + 1] : null;

            if ($state === 'line-comment') {
                if ($char === "\n" || $char === "\r") {
                    $output .= $char;
                    $state = 'normal';
                } else {
                    $output .= ' ';
                }

                continue;
            }

            if ($state === 'block-comment') {
                if ($char === '*' && $next === '/') {
                    $output .= '  ';
                    $index++;
                    $state = 'normal';
                } else {
                    $output .= ($char === "\n" || $char === "\r") ? $char : ' ';
                }

                continue;
            }

            if ($state === 'dollar-quote') {
                if ($dollarTerminator !== null && str_starts_with(substr($sql, $index), $dollarTerminator)) {
                    $output .= str_repeat(' ', strlen($dollarTerminator));
                    $index += strlen($dollarTerminator) - 1;
                    $state = 'normal';
                    $dollarTerminator = null;
                } else {
                    $output .= ($char === "\n" || $char === "\r") ? $char : ' ';
                }

                continue;
            }

            if (in_array($state, ['single-quote', 'double-quote', 'backtick'], true)) {
                $delimiter = match ($state) {
                    'single-quote' => "'",
                    'double-quote' => '"',
                    default => '`',
                };

                $output .= ' ';

                if ($char === '\\' && $next !== null) {
                    $output .= ' ';
                    $index++;

                    continue;
                }

                if ($char === $delimiter) {
                    if ($next === $delimiter) {
                        $output .= ' ';
                        $index++;
                    } else {
                        $state = 'normal';
                    }
                }

                continue;
            }

            if ($char === '-' && $next === '-') {
                $output .= '  ';
                $index++;
                $state = 'line-comment';

                continue;
            }

            if ($char === '#') {
                $output .= ' ';
                $state = 'line-comment';

                continue;
            }

            if ($char === '/' && $next === '*') {
                $output .= '  ';
                $index++;
                $state = 'block-comment';

                continue;
            }

            if ($char === '$') {
                $remaining = substr($sql, $index);

                if (preg_match('/^(\$[A-Za-z_][A-Za-z0-9_]*\$|\$\$)/', $remaining, $matches) === 1) {
                    $dollarTerminator = $matches[1];
                    $output .= str_repeat(' ', strlen($dollarTerminator));
                    $index += strlen($dollarTerminator) - 1;
                    $state = 'dollar-quote';

                    continue;
                }
            }

            if ($char === "'") {
                $output .= ' ';
                $state = 'single-quote';

                continue;
            }

            if ($char === '"') {
                $output .= ' ';
                $state = 'double-quote';

                continue;
            }

            if ($char === '`') {
                $output .= ' ';
                $state = 'backtick';

                continue;
            }

            $output .= $char;
        }

        return $output;
    }

    private function firstKeyword(string $sql): ?string
    {
        if (preg_match('/^([A-Z]+)/i', ltrim($sql), $matches) !== 1) {
            return null;
        }

        return strtoupper($matches[1]);
    }

    private function keywordAfterExplain(string $sql): ?string
    {
        $sql = preg_replace('/^EXPLAIN\s+(?:ANALYZE\s+)?/i', '', ltrim($sql)) ?? $sql;

        return $this->firstKeyword($sql);
    }

    /** @return list<string> */
    private function blockedKeywords(): array
    {
        return [
            'INSERT',
            'UPDATE',
            'DELETE',
            'DROP',
            'ALTER',
            'TRUNCATE',
            'CREATE',
            'REPLACE',
            'GRANT',
            'REVOKE',
            'RENAME',
            'MERGE',
            'CALL',
            'EXEC',
            'EXECUTE',
            'VACUUM',
            'ANALYZE',
            'ATTACH',
            'DETACH',
            'COPY',
            'LOAD',
            'LOCK',
            'UNLOCK',
            'SET',
            'RESET',
            'PRAGMA',
            'DO',
            'HANDLER',
            'INTO',
        ];
    }

    /** @return array<string, string> */
    private function blockedPatterns(): array
    {
        return [
            '/\bFOR\s+UPDATE\b/i' => 'Locking read FOR UPDATE tidak diperbolehkan.',
            '/\bFOR\s+SHARE\b/i' => 'Locking read FOR SHARE tidak diperbolehkan.',
            '/\bLOCK\s+IN\s+SHARE\s+MODE\b/i' => 'Locking read tidak diperbolehkan.',
            '/\bGET_LOCK\s*\(/i' => 'Fungsi locking tidak diperbolehkan.',
            '/\bRELEASE_LOCK\s*\(/i' => 'Fungsi locking tidak diperbolehkan.',
            '/\bSLEEP\s*\(/i' => 'Fungsi delay tidak diperbolehkan.',
            '/\bBENCHMARK\s*\(/i' => 'Fungsi benchmark tidak diperbolehkan.',
            '/\bPG_SLEEP\s*\(/i' => 'Fungsi delay tidak diperbolehkan.',
            '/\bOUTFILE\b/i' => 'File output tidak diperbolehkan.',
            '/\bDUMPFILE\b/i' => 'File output tidak diperbolehkan.',
        ];
    }
}
