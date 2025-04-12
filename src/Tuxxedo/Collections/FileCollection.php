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

namespace Tuxxedo\Collections;

class FileCollection
{
    private function __construct()
    {
    }

    /**
     * @return Collection<int, string>
     */
    public static function fromGlob(
        string $pattern,
        int $flags = 0,
    ): Collection {
        return new Collection(
            \glob($pattern, $flags) ?: [],
        );
    }

    /**
     * @return Collection<int, string>
     */
    public static function fromDirectory(
        string $directory,
    ): Collection {
        return self::fromGlob(
            pattern: $directory . '/',
        );
    }

    /**
     * @return Collection<int, string>
     */
    public static function fromFileType(
        string $directory,
        string $extension,
    ): Collection {
        return self::fromGlob(
            pattern: $directory . '/*.' . $extension,
        );
    }
}
