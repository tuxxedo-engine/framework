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

namespace Tuxxedo\Collection;

class FileCollection
{
    private function __construct()
    {
    }

    /**
     * @return string[]
     */
    private static function recursiveGlob(
        string $directory,
        string $extension = '',
        int $flags = 0,
    ): array {
        $files = \is_array($glob = \glob($directory . '/*' . $extension, $flags)) ? $glob : [];
        $iterator = \is_array($glob = \glob($directory . '/*', \GLOB_ONLYDIR | \GLOB_NOSORT)) ? $glob : [];

        foreach ($iterator as $dir) {
            $files = \array_merge(
                $files,
                self::recursiveGlob(
                    directory: $dir,
                    extension: $extension,
                    flags: $flags,
                ),
            );
        }

        return $files;
    }

    /**
     * @return Collection<int, string>
     */
    public static function fromGlob(
        string $pattern,
        int $flags = 0,
    ): Collection {
        return new Collection(
            ($glob = \glob($pattern, $flags)) !== false ? $glob : [],
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
    public static function fromRecursiveDirectory(
        string $directory,
    ): Collection {
        return new Collection(
            self::recursiveGlob(
                directory: $directory,
                flags: \GLOB_ONLYDIR | \GLOB_NOSORT,
            ),
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
            pattern: $directory . '/*' . $extension,
        );
    }

    /**
     * @return Collection<int, string>
     */
    public static function fromRecursiveFileType(
        string $directory,
        string $extension,
    ): Collection {
        return new Collection(
            self::recursiveGlob(
                directory: $directory,
                extension: $extension,
            ),
        );
    }
}
