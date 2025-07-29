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

namespace Tuxxedo\Console\Io;

class Output implements OutputInterface
{
    public function __construct(
        public readonly string $buffer,
    ) {
    }

    public function withBuffer(
        string $buffer,
    ): self {
        return new self(
            buffer: $buffer,
        );
    }
}
