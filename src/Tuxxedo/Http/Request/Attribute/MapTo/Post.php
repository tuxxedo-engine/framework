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

namespace Tuxxedo\Http\Request\Attribute\MapTo;

use Tuxxedo\Http\InputContext;
use Tuxxedo\Http\Request\Attribute\MapTo;

#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
class Post extends MapTo
{
    /**
     * @param class-string<object>|(\Closure(): object)|object|null $className
     */
    public function __construct(
        string $name,
        object|string|null $className = null,
    ) {
        parent::__construct($name, $className, InputContext::POST);
    }
}
