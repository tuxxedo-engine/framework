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

namespace Tuxxedo\Console\Io;

interface ReadableStreamInterface
{
    public function read(int $bytes): string;

    public function seek(int $position): void;
    public function rewind(): void;
    public function tell(): int;
    public function isEof(): bool;

    /**
     * @return resource
     */
    public function getStream();
}
