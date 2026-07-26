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

class LocalFile implements FileInterface
{
    public ?int $size {
        get {
            $result = @\filesize($this->path);

            return $result !== false
                ? $result
                : null;
        }
    }

    /**
     * @throws FileException
     */
    public function __construct(
        public readonly string $path,
        public readonly ?string $name = null,
        public readonly ?string $mimeType = null,
    ) {
        if (!\is_file($this->path)) {
            throw FileException::fromNotAFile(
                path: $this->path,
            );
        }
    }

    #[\NoDiscard]
    public function contents(): string
    {
        $result = @\file_get_contents($this->path);

        if ($result === false) {
            throw FileException::fromReadFailure(
                path: $this->path,
            );
        }

        return $result;
    }
}
