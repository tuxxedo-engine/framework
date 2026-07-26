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

use Tuxxedo\Collection\Collection;
use Tuxxedo\File\Storage\StoragePatternMatcher;

class FileCollectionFactory
{
    /**
     * @return Collection<int, string>
     *
     * @throws FileException
     */
    #[\NoDiscard]
    public static function paths(
        string $directory,
        string $pattern = '**',
    ): Collection {
        return new Collection(
            \iterator_to_array(
                self::walk(
                    directory: $directory,
                    pattern: $pattern,
                ),
                preserve_keys: false,
            ),
        );
    }

    /**
     * @return Collection<int, FileInterface>
     *
     * @throws FileException
     */
    #[\NoDiscard]
    public static function files(
        string $directory,
        string $pattern = '**',
    ): Collection {
        /** @var list<FileInterface> $collected */
        $collected = [];

        foreach (self::walk($directory, $pattern) as $path) {
            $collected[] = FileFactory::fromPath(
                path: $path,
            );
        }

        return new Collection($collected);
    }

    /**
     * @return \Generator<int, string>
     *
     * @throws FileException
     */
    private static function walk(
        string $directory,
        string $pattern,
    ): \Generator {
        $resolvedRoot = \realpath($directory);

        if ($resolvedRoot === false || !\is_dir($resolvedRoot)) {
            throw FileException::fromNotADirectory(
                path: $directory,
            );
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $resolvedRoot,
                \RecursiveDirectoryIterator::SKIP_DOTS,
            ),
        );

        $normalizedRoot = \str_replace('\\', '/', $resolvedRoot);
        $rootLength = \strlen($normalizedRoot) + 1;

        foreach ($iterator as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                continue; // @codeCoverageIgnore
            }

            if ($entry->isLink() || !$entry->isFile()) {
                continue; // @codeCoverageIgnore
            }

            $absolute = \str_replace('\\', '/', $entry->getPathname());
            $relative = \substr($absolute, $rootLength);

            if (!StoragePatternMatcher::matches($pattern, $relative)) {
                continue;
            }

            yield $absolute;
        }
    }
}
