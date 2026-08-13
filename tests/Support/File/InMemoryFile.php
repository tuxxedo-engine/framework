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

namespace Support\File;

use Tuxxedo\File\FileInterface;

class InMemoryFile implements FileInterface
{
    public ?int $size {
        get {
            return \strlen($this->bytes);
        }
    }

    public function __construct(
        private readonly string $bytes,
        public readonly ?string $name = null,
        public readonly ?string $mimeType = null,
    ) {
    }

    public function contents(): string
    {
        return $this->bytes;
    }
}
