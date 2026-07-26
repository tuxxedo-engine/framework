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

namespace Tuxxedo\File\Storage;

use Tuxxedo\File\FileInterface;

interface StorageInterface
{
    /**
     * @throws StorageException
     */
    #[\NoDiscard]
    public function read(
        string $key,
    ): FileInterface;

    /**
     * @throws StorageException
     */
    public function write(
        string $key,
        FileInterface $file,
    ): void;

    /**
     * @throws StorageException
     */
    public function delete(
        string $key,
    ): void;

    /**
     * @throws StorageException
     */
    #[\NoDiscard]
    public function exists(
        string $key,
    ): bool;

    /**
     * @return iterable<string>
     *
     * @throws StorageException
     */
    #[\NoDiscard]
    public function list(
        string $pattern = '**',
    ): iterable;
}
