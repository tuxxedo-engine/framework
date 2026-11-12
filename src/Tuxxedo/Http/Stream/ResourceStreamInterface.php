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

namespace Tuxxedo\Http\Stream;

interface ResourceStreamInterface extends StreamInterface
{
    public int $chunkSize {
        get;
    }

    /**
     * @throws StreamException
     */
    public function write(
        string $data,
    ): void;

    /**
     * @throws StreamException
     */
    public function rewind(): void;
}
