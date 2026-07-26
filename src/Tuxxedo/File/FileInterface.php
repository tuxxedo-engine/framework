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

namespace Tuxxedo\File;

interface FileInterface
{
    public ?string $name {
        get;
    }

    public ?string $mimeType {
        get;
    }

    public ?int $size {
        get;
    }

    /**
     * @throws FileException
     */
    #[\NoDiscard]
    public function contents(): string;
}
