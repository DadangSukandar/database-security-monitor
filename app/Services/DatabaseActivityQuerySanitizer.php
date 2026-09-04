<?php

namespace App\Services;

class DatabaseActivityQuerySanitizer
{
    public function sanitize(
        string $query,
        ?string $driver = null
    ): string {
        $query = $this->redactComments(
            $query
        );

        $query = $this->redactQuotedStrings(
            $query,
            $driver === 'mysql'
        );

        return $this->redactNumericLiterals(
            $query
        );
    }

    private function redactComments(
        string $query
    ): string {
        $query = preg_replace(
            '/\/\*.*?\*\//s',
            '/* redacted */',
            $query
        ) ?? $query;

        $query = preg_replace(
            '/--[^\r\n]*/',
            '-- redacted',
            $query
        ) ?? $query;

        return preg_replace(
            '/#[^\r\n]*/',
            '# redacted',
            $query
        ) ?? $query;
    }

    private function redactQuotedStrings(
        string $query,
        bool $redactDoubleQuoted
    ): string {
        $length =
            strlen($query);

        $result = '';

        $index = 0;

        while ($index < $length) {
            $character =
                $query[$index];

            /*
            |--------------------------------------------------------------------------
            | PostgreSQL Dollar-Quoted Literal
            |--------------------------------------------------------------------------
            */

            if ($character === '$') {
                $remaining =
                    substr(
                        $query,
                        $index
                    );

                if (
                    preg_match(
                        '/^\$[A-Za-z_][A-Za-z0-9_]*\$|^\$\$/',
                        $remaining,
                        $match
                    ) === 1
                ) {
                    $delimiter =
                        $match[0];

                    $contentStart =
                        $index +
                        strlen(
                            $delimiter
                        );

                    $closingPosition =
                        strpos(
                            $query,
                            $delimiter,
                            $contentStart
                        );

                    if (
                        $closingPosition !==
                        false
                    ) {
                        $result .=
                            $delimiter.
                            '?'.
                            $delimiter;

                        $index =
                            $closingPosition +
                            strlen(
                                $delimiter
                            );

                        continue;
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Quoted Literal
            |--------------------------------------------------------------------------
            |
            | Single quote:
            |
            |     'secret'
            |
            | MySQL juga dapat menggunakan:
            |
            |     "secret"
            |
            | PostgreSQL double quote dipertahankan karena merupakan identifier.
            |
            */

            $isSingleQuote =
                $character === "'";

            $isMysqlDoubleQuote =
                $redactDoubleQuoted &&
                $character === '"';

            if (
                ! $isSingleQuote &&
                ! $isMysqlDoubleQuote
            ) {
                $result .=
                    $character;

                $index++;

                continue;
            }

            $quote =
                $character;

            $result .=
                $quote.
                '?'.
                $quote;

            $index++;

            while ($index < $length) {
                /*
                |--------------------------------------------------------------------------
                | Escaped Quote
                |--------------------------------------------------------------------------
                |
                | 'O''Brien'
                |
                | "value""value"
                |
                */

                if (
                    $query[$index] === $quote &&
                    $index + 1 < $length &&
                    $query[$index + 1] === $quote
                ) {
                    $index += 2;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Backslash Escape
                |--------------------------------------------------------------------------
                */

                if (
                    $query[$index] === '\\' &&
                    $index + 1 < $length
                ) {
                    $index += 2;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Closing Quote
                |--------------------------------------------------------------------------
                */

                if (
                    $query[$index] === $quote
                ) {
                    $index++;

                    break;
                }

                $index++;
            }
        }

        return $result;
    }

    private function redactNumericLiterals(
        string $query
    ): string {
        return preg_replace(
            '/(?<![\w$])'.
            '(?:'.
                '0x[0-9a-fA-F]+'.
                '|'.
                '\d+(?:\.\d+)?(?:[eE][+-]?\d+)?'.
            ')'.
            '(?![\w$])/',
            '?',
            $query
        ) ?? $query;
    }
}
