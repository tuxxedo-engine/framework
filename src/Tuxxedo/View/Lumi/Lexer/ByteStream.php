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

namespace Tuxxedo\View\Lumi\Lexer;

class ByteStream implements ByteStreamInterface
{
    public readonly string $input;
    public readonly int $length;
    public private(set) int $position = 0;
    public private(set) int $line = 1;

    final private function __construct(
        string $input,
    ) {
        $this->input = \str_replace(
            [
                "\r\n",
                "\r",
            ],
            "\n",
            $input,
        );

        $this->length = \strlen($this->input);
    }

    public function __clone()
    {
        $this->position = 0;
        $this->line = 1;
    }

    public static function createFromString(string $input): static
    {
        return new static($input);
    }

    public static function createFromFile(string $filename): static
    {
        $contents = @\file_get_contents($filename);

        if ($contents === false) {
            throw LexerException::fromFileNotFound(
                filename: $filename,
            );
        }

        return new static($contents);
    }

    public function eof(): bool
    {
        return $this->position >= $this->length;
    }

    public function peek(
        int $length,
        bool $skipWhitespace = false,
    ): string {
        $position = $this->position;

        if ($skipWhitespace) {
            $position += $this->whitespaceLength($position);
        }

        return \substr($this->input, $position, $length);
    }

    public function peekCharacter(): string
    {
        if ($this->eof()) {
            return '';
        }

        return \substr($this->input, $this->position, $this->characterLength($this->position));
    }

    public function findSequenceOutsideQuotes(
        string $sequence,
        int $offset = 0,
    ): ?int {
        $quoteChar = null;
        $sequenceLength = \strlen($sequence);
        $lastStart = $this->length - $sequenceLength;
        $stops = '\\"\'' . $sequence[0];

        for ($i = $this->position + $offset; $i < $this->length; $i++) {
            $i += \strcspn($this->input, $quoteChar === null ? $stops : '\\' . $quoteChar, $i);

            if ($i >= $this->length) {
                return null;
            }

            $char = $this->input[$i];

            if ($char === '\\') {
                $i++;

                continue;
            }

            if ($char === '"' || $char === "'") {
                if ($quoteChar === null) {
                    $quoteChar = $char;
                } elseif ($quoteChar === $char) {
                    $quoteChar = null;
                }

                continue;
            }

            if ($i > $lastStart) {
                return null;
            }

            if (\substr_compare($this->input, $sequence, $i, $sequenceLength) === 0) {
                return $i - $this->position;
            }
        }

        return null;
    }

    public function consume(): string
    {
        if ($this->eof()) {
            throw LexerException::fromTokenStreamEof();
        }

        $char = $this->input[$this->position];
        $this->position++;

        if ($char === "\n") {
            $this->line++;
        }

        return $char;
    }

    public function consumeCharacter(): string
    {
        if ($this->eof()) {
            throw LexerException::fromTokenStreamEof();
        }

        $char = $this->peekCharacter();
        $this->position += \strlen($char);

        if ($char === "\n") {
            $this->line++;
        }

        return $char;
    }

    public function consumeSequence(
        string $sequence,
    ): void {
        $length = \strlen($sequence);

        if (\substr($this->input, $this->position, $length) !== $sequence) {
            throw LexerException::fromUnexpectedSequenceFound(
                sequence: $sequence,
                line: $this->line,
            );
        }

        $this->position += $length;
        $this->line += \substr_count($sequence, "\n");
    }

    public function consumeWhitespace(): bool
    {
        $length = $this->whitespaceLength($this->position);

        if ($length > 0) {
            $this->line += \substr_count($this->input, "\n", $this->position, $length);
            $this->position += $length;
        }

        return !$this->eof();
    }

    private function characterLength(
        int $position,
    ): int {
        $byte = \ord($this->input[$position]);

        $length = match (true) {
            $byte >= 0xF0 => 4,
            $byte >= 0xE0 => 3,
            $byte >= 0xC0 => 2,
            default => 1,
        };

        return \min($length, $this->length - $position);
    }

    private function whitespaceLength(
        int $position,
    ): int {
        $start = $position;

        while ($position < $this->length) {
            $ascii = \strspn($this->input, " \t\n\r\v\f", $position);

            if ($ascii > 0) {
                $position += $ascii;

                continue;
            }

            if (\ord($this->input[$position]) < 0x80) {
                break;
            }

            $length = $this->characterLength($position);

            if (\preg_match('/^\s$/u', \substr($this->input, $position, $length)) !== 1) {
                break;
            }

            $position += $length;
        }

        return $position - $start;
    }
}
