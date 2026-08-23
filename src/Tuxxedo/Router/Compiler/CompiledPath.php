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

readonly class CompiledPath implements CompiledPathInterface
{
    /**
     * @param list<ArgumentNode> $argumentNodes
     */
    public function __construct(
        public string $regexPath,
        public array $argumentNodes,
    ) {
    }
}
