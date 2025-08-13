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

    /**
     * @phpstan-impure
     */
    public function eof(): bool;

    public function peekAhead(
        int $length,
    ): string;

    public function peekAheadSequence(
        string $sequence,
        int $offset,
    ): bool;

    public function match(
        string $sequence,
    ): bool;

    public function consume(): string;

    public function consumeSequence(
        string $sequence,
    ): void;
}
