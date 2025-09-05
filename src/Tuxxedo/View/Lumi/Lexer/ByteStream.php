<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
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

        $this->length = \mb_strlen($this->input);
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
        $input = $this->input;
        $position = $this->position;

        if ($skipWhitespace) {
            $len = \mb_strlen($input);

            while ($position < $len) {
                $char = \mb_substr($input, $position, 1);

                if (\preg_match('/\s/u', $char) !== 1) {
                    break;
                }

                $position++;
            }
        }

        return \mb_substr($input, $position, $length);
    }

    public function findSequenceOutsideQuotes(
        string $sequence,
        int $offset = 0,
    ): ?int {
        $quoteChar = null;
        $escaped = false;
        $sequenceLength = \mb_strlen($sequence);

        for ($i = $this->position + $offset; $i < $this->length; $i++) {
            $char = \mb_substr($this->input, $i, 1);

            if ($escaped) {
                $escaped = false;

                continue;
            }

            if ($char === '\\') {
                $escaped = true;

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

            if ($quoteChar === null) {
                if ($i + $sequenceLength > $this->length) {
                    return null;
                }

                $chunk = \mb_substr($this->input, $i, $sequenceLength);

                if ($chunk === $sequence) {
                    return $i - $this->position;
                }
            }
        }

        return null;
    }

    public function consume(): string
    {
        if ($this->eof()) {
            throw LexerException::fromEofReached();
        }

        $char = \mb_substr($this->input, $this->position, 1);
        $this->position++;

        if ($char === "\n") {
            $this->line++;
        }

        return $char;
    }

    public function consumeSequence(
        string $sequence,
    ): void {
        if ($this->peek(\mb_strlen($sequence)) !== $sequence) {
            throw LexerException::fromUnexpectedSequenceFound(
                sequence: $sequence,
            );
        }

        $this->position += \mb_strlen($sequence);
        $this->line += \substr_count($sequence, "\n");
    }

    public function consumeWhitespace(): bool
    {
        while (!$this->eof() && \preg_match('/\s/u', $this->peek(1)) === 1) {
            $this->consume();
        }

        return !$this->eof();
    }
}
