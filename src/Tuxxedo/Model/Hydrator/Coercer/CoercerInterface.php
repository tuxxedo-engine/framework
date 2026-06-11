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

namespace Tuxxedo\Model\Hydrator\Coercer;

interface CoercerInterface
{
    public function hydrate(
        string|int|float|bool $value,
    ): mixed;

    public function dehydrate(
        mixed $value,
    ): string|int|float|bool;
}
