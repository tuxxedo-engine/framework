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

class TokenStream implements TokenStreamInterface
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

    public function peekAhead(int $length): string
    {
        return \mb_substr($this->input, $this->position, $length, 'UTF-8');
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

    public function match(string $sequence): bool
    {
        return $this->peekAhead(\mb_strlen($sequence, 'UTF-8')) === $sequence;
    }

    public function consumeSequence(string $sequence): void
    {
        if (!$this->match($sequence)) {
            throw LexerException::fromSequenceNotFound(
                sequence: $sequence,
            );
        }

        $this->position += \mb_strlen($sequence, 'UTF-8');
    }
}
