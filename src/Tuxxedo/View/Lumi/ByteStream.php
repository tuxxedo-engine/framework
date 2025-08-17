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

namespace Tuxxedo\View\Lumi;

use Tuxxedo\View\Lumi\Lexer\LexerException;

class ByteStream implements ByteStreamInterface
{
    public readonly int $length;
    public private(set) int $position = 0;

    final private function __construct(
        public readonly string $input,
    ) {
        $this->length = \mb_strlen($this->input, 'UTF-8');
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
            $len = \mb_strlen($input, 'UTF-8');

            while ($position < $len) {
                $char = \mb_substr($input, $position, 1, 'UTF-8');

                if (\preg_match('/\s/u', $char) !== 1) {
                    break;
                }

                $position++;
            }
        }

        return \mb_substr($input, $position, $length, 'UTF-8');
    }

    public function peekSequence(
        string $sequence,
        int $offset,
    ): bool {
        return \mb_strpos(
            \mb_substr($this->input, $this->position + $offset, null, 'UTF-8'),
            $sequence
        ) !== false;
    }

    public function match(
        string $sequence,
    ): bool {
        return $this->peek(\mb_strlen($sequence, 'UTF-8')) === $sequence;
    }

    public function consume(): string
    {
        if ($this->eof()) {
            throw LexerException::fromEofReached();
        }

        $char = \mb_substr($this->input, $this->position, 1, 'UTF-8');
        $this->position++;
        return $char;
    }

    public function consumeSequence(
        string $sequence,
    ): void {
        if (!$this->match($sequence)) {
            throw LexerException::fromSequenceNotFound(
                sequence: $sequence,
            );
        }

        $this->position += \mb_strlen($sequence, 'UTF-8');
    }

    public function consumeWhitespace(): bool
    {
        while (!$this->eof() && \preg_match('/\s/u', $this->peek(1)) === 1) {
            $this->consume();
        }

        return !$this->eof();
    }
}
