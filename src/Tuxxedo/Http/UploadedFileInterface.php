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

namespace Tuxxedo\Http;

use Tuxxedo\File\FileInterface;

interface UploadedFileInterface extends FileInterface
{
    public string $name {
        get;
    }

    public string $mimeType {
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

    public function moveTo(
        string $fileName,
    ): bool;
}
