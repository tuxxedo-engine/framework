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

// @todo Cleanup this interface
interface ByteStreamInterface
{
    public string $input {
        get;
    }

    public int $length {
        get;
    }

    public int $position {
        get;
    }

    public int $line {
        get;
    }

    /**
     * @phpstan-impure
     */
    public function eof(): bool;

    public function peek(
        int $length,
        bool $skipWhitespace = false,
    ): string;

    public function peekSequence(
        string $sequence,
        int $offset,
    ): bool;

    public function match(
        string $sequence,
    ): bool;

    public function matchSequenceOutsideQuotes(
        string $sequence,
        int $offset = 0,
    ): ?int;

    public function consume(): string;

    public function consumeSequence(
        string $sequence,
    ): void;

    public function consumeWhitespace(): bool;
}
