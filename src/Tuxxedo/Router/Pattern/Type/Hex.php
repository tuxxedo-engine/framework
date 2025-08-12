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

class Hex implements TypePatternInterface
{
    public private(set) string $name = 'hex';
    public private(set) string $regex = '[0-9a-fA-F]+';
}
