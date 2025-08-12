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

class LanguageCode implements TypePatternInterface
{
    public private(set) string $name = 'language-code';
    public private(set) string $regex = '[a-z]{2}(-[A-Z]{2})?';
}
