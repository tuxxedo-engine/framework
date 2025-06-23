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

namespace Tuxxedo\Http;

interface UploadedFileInterface
{
    public string $name {
        get;
    }

    public string $type {
        get;
    }

    public int $size {
        get;
    }

    public string $temporaryPath {
        get;
    }

    public string $browserPath {
        get;
    }

    public function isTrustedType(): bool;

    public function getContents(): ?string;

    public function moveTo(
        string $fileName,
    ): bool;
}
