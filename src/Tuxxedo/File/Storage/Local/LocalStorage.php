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

namespace Tuxxedo\File\Storage\Local;

use Tuxxedo\File\FileFactory;
use Tuxxedo\File\FileInterface;
use Tuxxedo\File\LocalFile;
use Tuxxedo\File\Storage\Local\Config\LocalStorageConfigInterface;
use Tuxxedo\File\Storage\StorageException;
use Tuxxedo\File\Storage\StorageInterface;
use Tuxxedo\File\Storage\StoragePatternMatcher;

class LocalStorage implements StorageInterface
{
    private readonly string $resolvedRoot;

    /**
     * @throws StorageException
     */
    public function __construct(
        private readonly LocalStorageConfigInterface $config,
    ) {
        $resolved = \realpath($this->config->root);

        if ($resolved === false || !\is_dir($resolved)) {
            throw StorageException::fromMissingRoot(
                root: $this->config->root,
            );
        }

        $this->resolvedRoot = $resolved;

        if (
            !$this->config->allowCaseInsensitiveFilesystem &&
            self::detectCaseInsensitivity($this->resolvedRoot)
        ) {
            // @codeCoverageIgnoreStart
            throw StorageException::fromCaseInsensitiveFilesystem(
                root: $this->config->root,
            );
            // @codeCoverageIgnoreEnd
        }
    }

    public function read(
        string $key,
    ): FileInterface {
        return FileFactory::fromPath(
            path: $this->resolveExisting(
                key: $key,
            ),
        );
    }

    public function write(
        string $key,
        FileInterface $file,
    ): void {
        $this->validateKey(
            key: $key,
        );

        $target = $this->resolvedRoot . '/' . $key;

        if ($this->config->autoCreateDirectories) {
            $parent = \dirname($target);

            if (!\is_dir($parent) && !@\mkdir($parent, 0755, true) && !\is_dir($parent)) {
                // @codeCoverageIgnoreStart
                throw StorageException::fromWriteFailure(
                    key: $key,
                );
                // @codeCoverageIgnoreEnd
            }
        }

        if ($file instanceof LocalFile) {
            if (!@\copy($file->path, $target)) {
                // @codeCoverageIgnoreStart
                throw StorageException::fromWriteFailure(
                    key: $key,
                );
                // @codeCoverageIgnoreEnd
            }

            return;
        }

        if (@\file_put_contents($target, $file->contents()) === false) {
            throw StorageException::fromWriteFailure(
                key: $key,
            );
        }
    }

    public function delete(
        string $key,
    ): void {
        $path = $this->resolveExisting(
            key: $key,
        );

        if (!@\unlink($path)) {
            // @codeCoverageIgnoreStart
            throw StorageException::fromDeleteFailure(
                key: $key,
            );
            // @codeCoverageIgnoreEnd
        }
    }

    public function exists(
        string $key,
    ): bool {
        try {
            $this->validateKey(
                key: $key,
            );
        } catch (StorageException) {
            return false;
        }

        $path = $this->resolvedRoot . '/' . $key;

        if (!\is_file($path)) {
            return false;
        }

        return $this->caseMatches(
            key: $key,
            path: $path,
        );
    }

    public function list(
        string $pattern = '**',
    ): iterable {
        $scanBase = $this->resolvedRoot;
        $literalPrefix = StoragePatternMatcher::literalPrefix(
            pattern: $pattern,
        );

        if ($literalPrefix !== '') {
            $lastSlash = \strrpos($literalPrefix, '/');
            $prefixDir = $lastSlash !== false
                ? \substr($literalPrefix, 0, $lastSlash + 1)
                : '';

            if ($prefixDir !== '') {
                $candidate = $this->resolvedRoot . '/' . \rtrim($prefixDir, '/');

                if (!\is_dir($candidate)) {
                    return;
                }

                $scanBase = $candidate;
            }
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $scanBase,
                \RecursiveDirectoryIterator::SKIP_DOTS,
            ),
        );

        $rootLength = \strlen($this->resolvedRoot) + 1;

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue; // @codeCoverageIgnore
            }

            if ($file->isLink() || !$file->isFile()) {
                continue; // @codeCoverageIgnore
            }

            $absolute = $file->getPathname();
            $relative = \substr($absolute, $rootLength);
            $key = \str_replace('\\', '/', $relative);

            if (!StoragePatternMatcher::matches($pattern, $key)) {
                continue;
            }

            yield $key;
        }
    }

    /**
     * @throws StorageException
     */
    private function resolveExisting(
        string $key,
    ): string {
        $this->validateKey(
            key: $key,
        );

        $target = $this->resolvedRoot . '/' . $key;

        if (!\is_file($target)) {
            throw StorageException::fromKeyNotFound(
                key: $key,
            );
        }

        $resolvedTarget = \realpath($target);

        if (
            $resolvedTarget === false ||
            !\str_starts_with($resolvedTarget, $this->resolvedRoot . \DIRECTORY_SEPARATOR)
        ) {
            // @codeCoverageIgnoreStart
            throw StorageException::fromKeyEscapesRoot(
                key: $key,
            );
            // @codeCoverageIgnoreEnd
        }

        if (!$this->caseMatches($key, $target)) {
            // @codeCoverageIgnoreStart
            throw StorageException::fromCaseMismatch(
                key: $key,
                actualCase: \str_replace('\\', '/', \substr($resolvedTarget, \strlen($this->resolvedRoot) + 1)),
            );
            // @codeCoverageIgnoreEnd
        }

        return $target;
    }

    /**
     * @throws StorageException
     */
    private function validateKey(
        string $key,
    ): void {
        if ($key === '' || \str_contains($key, "\x00")) {
            throw StorageException::fromInvalidKey(
                key: $key,
            );
        }

        if (\str_contains($key, '\\')) {
            throw StorageException::fromInvalidKey(
                key: $key,
            );
        }

        foreach (\explode('/', $key) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw StorageException::fromInvalidKey(
                    key: $key,
                );
            }
        }
    }

    private function caseMatches(
        string $key,
        string $path,
    ): bool {
        if (!$this->config->allowCaseInsensitiveFilesystem) {
            return true; // @codeCoverageIgnore
        }

        $resolved = \realpath($path);

        if ($resolved === false) {
            return false; // @codeCoverageIgnore
        }

        $actualRelative = \str_replace(
            '\\',
            '/',
            \substr($resolved, \strlen($this->resolvedRoot) + 1),
        );

        return $actualRelative === $key;
    }

    /**
     * @codeCoverageIgnore
     */
    private static function detectCaseInsensitivity(
        string $root,
    ): bool {
        $suffix = 'Aa' . \bin2hex(\random_bytes(8));
        $probeName = '.tuxxedo-' . $suffix;
        $probePath = $root . '/' . $probeName;

        if (@\touch($probePath) === false) {
            return false; // @codeCoverageIgnore
        }

        $swappedName = '.tuxxedo-' . \strtoupper($suffix);
        $swappedPath = $root . '/' . $swappedName;

        try {
            return \is_file($swappedPath);
        } finally {
            @\unlink($probePath);
        }
    }
}
