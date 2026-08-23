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

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Router\PrefixInterface;

#[DefaultImplementation(class: PathCompiler::class, lifecycle: Lifecycle::SINGLETON)]
interface PathCompilerInterface
{
    public function compile(
        string $path,
        ?PrefixInterface $prefix = null,
    ): CompiledPathInterface;
}
