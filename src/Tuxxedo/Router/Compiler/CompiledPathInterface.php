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

namespace Tuxxedo\Router\Compiler;

use Tuxxedo\Router\ArgumentNode;

interface CompiledPathInterface
{
    public string $regexPath {
        get;
    }

    /**
     * @var list<ArgumentNode>
     */
    public array $argumentNodes {
        get;
    }
}
