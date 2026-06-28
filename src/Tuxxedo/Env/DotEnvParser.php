<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Env;

class DotEnvParser
{
    private const string KEY_PATTERN = '/\A[A-Za-z_][A-Za-z0-9_]*\z/';
    private const string INT_PATTERN = '/\A-?\d+\z/';
    private const string FLOAT_PATTERN = '/\A-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?\z/';

    private string $contents = '';
    private string $file = '';
    private int $length = 0;
    private int $position = 0;
    private int $line = 1;

    /**
     * @return array<string, string|int|float|bool>
     *
     * @throws EnvException
     */
    public function parse(
        string $contents,
        string $file,
    ): array {
        $this->reset(
            contents: $contents,
            file: $file,
        );

        $result = [];

        while ($this->position < $this->length) {
            $this->skipBlanks();

            if ($this->position >= $this->length) {
                break;
            }

            $entryLine = $this->line;

            $this->skipExportPrefix();

            $key = $this->readKey(
                entryLine: $entryLine,
            );

            if (\array_key_exists($key, $result)) {
                throw EnvException::fromDuplicateKey(
                    file: $this->file,
                    line: $entryLine,
                    key: $key,
                );
            }

            $this->expectEquals(
                key: $key,
                entryLine: $entryLine,
            );

            $this->skipSpaces();

            if (
                $this->position < $this->length &&
                $this->contents[$this->position] === "'"
            ) {
                $this->position++;

                $result[$key] = $this->readSingleQuoted(
                    entryLine: $entryLine,
                );
            } elseif (
                $this->position < $this->length &&
                $this->contents[$this->position] === '"'
            ) {
                $this->position++;

                $result[$key] = $this->readDoubleQuoted(
                    resolved: $result,
                    entryLine: $entryLine,
                );
            } else {
                $result[$key] = $this->hydrate(
                    value: $this->readUnquoted(
                        resolved: $result,
                    ),
                );
            }

            $this->skipTrailing();
        }

        return $result;
    }

    private function reset(
        string $contents,
        string $file,
    ): void {
        if (\str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = \substr($contents, 3);
        }

        $normalized = \preg_replace('/\r\n?/', "\n", $contents) ?? $contents;

        $this->contents = $normalized;
        $this->file = $file;
        $this->length = \strlen($normalized);
        $this->position = 0;
        $this->line = 1;
    }

    private function skipBlanks(): void
    {
        while ($this->position < $this->length) {
            $char = $this->contents[$this->position];

            if ($char === "\n") {
                $this->line++;
                $this->position++;

                continue;
            }

            if ($char === ' ' || $char === "\t") {
                $this->position++;

                continue;
            }

            if ($char === '#') {
                while (
                    $this->position < $this->length &&
                    $this->contents[$this->position] !== "\n"
                ) {
                    $this->position++;
                }

                continue;
            }

            break;
        }
    }

    private function skipSpaces(): void
    {
        while (
            $this->position < $this->length &&
            (
                $this->contents[$this->position] === ' ' ||
                $this->contents[$this->position] === "\t"
            )
        ) {
            $this->position++;
        }
    }

    private function skipExportPrefix(): void
    {
        if (\substr($this->contents, $this->position, 7) === 'export ') {
            $this->position += 7;
            $this->skipSpaces();
        }
    }

    /**
     * @throws EnvException
     */
    private function readKey(
        int $entryLine,
    ): string {
        $start = $this->position;

        while ($this->position < $this->length) {
            $char = $this->contents[$this->position];

            if ($char === '=' || $char === ' ' || $char === "\t" || $char === "\n") {
                break;
            }

            $this->position++;
        }

        $key = \substr($this->contents, $start, $this->position - $start);

        if ($key === '' || \preg_match(self::KEY_PATTERN, $key) !== 1) {
            throw EnvException::fromInvalidKey(
                file: $this->file,
                line: $entryLine,
                key: $key,
            );
        }

        return $key;
    }

    /**
     * @throws EnvException
     */
    private function expectEquals(
        string $key,
        int $entryLine,
    ): void {
        $this->skipSpaces();

        if (
            $this->position >= $this->length ||
            $this->contents[$this->position] !== '='
        ) {
            throw EnvException::fromMissingEquals(
                file: $this->file,
                line: $entryLine,
                key: $key,
            );
        }

        $this->position++;
    }

    /**
     * @throws EnvException
     */
    private function readSingleQuoted(
        int $entryLine,
    ): string {
        $value = '';

        while ($this->position < $this->length) {
            $char = $this->contents[$this->position];

            if ($char === "'") {
                $this->position++;

                return $value;
            }

            if ($char === "\n") {
                $this->line++;
            }

            $value .= $char;

            $this->position++;
        }

        throw EnvException::fromUnclosedQuote(
            file: $this->file,
            line: $entryLine,
            quote: 'single',
        );
    }

    /**
     * @param array<string, string|int|float|bool> $resolved
     *
     * @throws EnvException
     */
    private function readDoubleQuoted(
        array $resolved,
        int $entryLine,
    ): string {
        $value = '';

        while ($this->position < $this->length) {
            $char = $this->contents[$this->position];

            if ($char === '"') {
                $this->position++;

                return $value;
            }

            if ($char === '\\') {
                if ($this->position + 1 >= $this->length) {
                    break;
                }

                $sequence = $this->contents[$this->position + 1];

                $value .= match ($sequence) {
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    '\\' => '\\',
                    '"' => '"',
                    '$' => '$',
                    default => throw EnvException::fromUnknownEscapeSequence(
                        file: $this->file,
                        line: $this->line,
                        sequence: $sequence,
                    ),
                };

                $this->position += 2;

                continue;
            }

            if (
                $char === '$' &&
                $this->position + 1 < $this->length &&
                $this->contents[$this->position + 1] === '{'
            ) {
                $value .= $this->readInterpolation(
                    resolved: $resolved,
                );

                continue;
            }

            if ($char === "\n") {
                $this->line++;
            }

            $value .= $char;

            $this->position++;
        }

        throw EnvException::fromUnclosedQuote(
            file: $this->file,
            line: $entryLine,
            quote: 'double',
        );
    }

    /**
     * @param array<string, string|int|float|bool> $resolved
     *
     * @throws EnvException
     */
    private function readUnquoted(
        array $resolved,
    ): string {
        $value = '';
        $previousWasSpace = true;

        while ($this->position < $this->length) {
            $char = $this->contents[$this->position];

            if ($char === "\n") {
                break;
            }

            if ($char === '#' && $previousWasSpace) {
                break;
            }

            if (
                $char === '\\' &&
                $this->position + 1 < $this->length &&
                $this->contents[$this->position + 1] === '$'
            ) {
                $value .= '$';
                $this->position += 2;
                $previousWasSpace = false;

                continue;
            }

            if (
                $char === '$' &&
                $this->position + 1 < $this->length &&
                $this->contents[$this->position + 1] === '{'
            ) {
                $value .= $this->readInterpolation(
                    resolved: $resolved,
                );

                $previousWasSpace = false;

                continue;
            }

            $value .= $char;
            $previousWasSpace = $char === ' ' || $char === "\t";

            $this->position++;
        }

        return \rtrim($value);
    }

    /**
     * @param array<string, string|int|float|bool> $resolved
     *
     * @throws EnvException
     */
    private function readInterpolation(
        array $resolved,
    ): string {
        $line = $this->line;
        $this->position += 2;
        $start = $this->position;

        while (
            $this->position < $this->length &&
            $this->contents[$this->position] !== '}'
        ) {
            if ($this->contents[$this->position] === "\n") {
                throw EnvException::fromInterpolationContainsNewline(
                    file: $this->file,
                    line: $line,
                );
            }

            $this->position++;
        }

        if ($this->position >= $this->length) {
            throw EnvException::fromUnterminatedInterpolation(
                file: $this->file,
                line: $line,
            );
        }

        $varName = \substr($this->contents, $start, $this->position - $start);

        if (\preg_match(self::KEY_PATTERN, $varName) !== 1) {
            throw EnvException::fromInvalidInterpolationVariable(
                file: $this->file,
                line: $line,
                name: $varName,
            );
        }

        if (!\array_key_exists($varName, $resolved)) {
            throw EnvException::fromUnresolvedInterpolation(
                file: $this->file,
                line: $line,
                reference: $varName,
            );
        }

        $this->position++;

        return (string) $resolved[$varName];
    }

    /**
     * @throws EnvException
     */
    private function skipTrailing(): void
    {
        while (
            $this->position < $this->length &&
            $this->contents[$this->position] !== "\n"
        ) {
            $char = $this->contents[$this->position];

            if ($char === '#') {
                while (
                    $this->position < $this->length &&
                    $this->contents[$this->position] !== "\n"
                ) {
                    $this->position++;
                }

                break;
            }

            if ($char !== ' ' && $char !== "\t") {
                throw EnvException::fromUnexpectedCharacter(
                    file: $this->file,
                    line: $this->line,
                    character: $char,
                );
            }

            $this->position++;
        }
    }

    private function hydrate(
        string $value,
    ): string|int|float|bool {
        $lower = \strtolower($value);

        if ($lower === 'true') {
            return true;
        }

        if ($lower === 'false') {
            return false;
        }

        if (\preg_match(self::INT_PATTERN, $value) === 1) {
            return (int) $value;
        }

        if (\preg_match(self::FLOAT_PATTERN, $value) === 1) {
            return (float) $value;
        }

        return $value;
    }
}
