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

namespace Tuxxedo\Http\Response\Stream;

use Tuxxedo\Http\Response\PrefersHeadersInterface;

// @todo Consider a serializer or similar argument for generator based streams for selective restructuring
interface StreamInterface extends PrefersHeadersInterface
{
    public bool $autoFlush {
        get;
    }

    public bool $closed {
        get;
    }

    public function close(): void;
    public function eof(): bool;

    public function getSize(): ?int;

    public function read(): ?string;
    public function getContents(): string;
}
