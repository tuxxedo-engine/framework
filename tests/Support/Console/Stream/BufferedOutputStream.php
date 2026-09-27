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

namespace Support\Console\Stream;

use Tuxxedo\Console\Stream\OutputStreamInterface;

class BufferedOutputStream implements OutputStreamInterface
{
    public private(set) string $bytes = '';
    public private(set) bool $closed = false;

    public function __construct(
        public readonly bool $isTerminal = false,
    ) {
    }

    public function write(
        string $bytes,
    ): void {
        $this->bytes .= $bytes;
    }

    public function close(): void
    {
        $this->closed = true;
    }
}
