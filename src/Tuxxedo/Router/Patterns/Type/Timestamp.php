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

namespace Tuxxedo\Router\Patterns\Type;

use Tuxxedo\Router\Patterns\TypePatternInterface;

class Timestamp implements TypePatternInterface
{
    public private(set) string $name = 'timestamp';
    public private(set) string $regex = '\d{10}';
}
