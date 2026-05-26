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

interface StreamProxyInterface
{
    public function eof(): bool;

    public function getSize(): ?int;

    public function read(): ?string;
    public function contents(): string;
}
