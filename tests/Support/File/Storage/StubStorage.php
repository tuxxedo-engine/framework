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

namespace Support\File\Storage;

use Tuxxedo\File\FileInterface;
use Tuxxedo\File\Storage\StorageInterface;

class StubStorage implements StorageInterface
{
    public function read(
        string $key,
    ): FileInterface {
        throw new \LogicException('StubStorage: read not implemented');
    }

    public function write(
        string $key,
        FileInterface $file,
    ): void {
        throw new \LogicException('StubStorage: write not implemented');
    }

    public function delete(
        string $key,
    ): void {
        throw new \LogicException('StubStorage: delete not implemented');
    }

    public function exists(
        string $key,
    ): bool {
        return false;
    }

    public function list(
        string $pattern = '**',
    ): iterable {
        return [];
    }
}
