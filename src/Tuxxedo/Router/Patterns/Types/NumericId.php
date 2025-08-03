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

namespace Tuxxedo\Router\Patterns\Types;

use Tuxxedo\Router\Patterns\TypePatternInterface;

class NumericId implements TypePatternInterface
{
    public private(set) string $name = 'numeric-id';
    public private(set) string $regex = '\d+';
}
