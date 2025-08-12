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

namespace Tuxxedo\Router\Pattern\Type;

use Tuxxedo\Router\Pattern\TypePatternInterface;

class UuidV4 implements TypePatternInterface
{
    public private(set) string $name = 'uuid-v4';
    public private(set) string $regex = '[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}';
}
