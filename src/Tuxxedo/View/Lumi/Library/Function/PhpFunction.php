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

namespace Tuxxedo\View\Lumi\Library\Function;

use Tuxxedo\View\Lumi\Library\Directive\DirectivesInterface;
use Tuxxedo\View\ViewRenderInterface;

class PhpFunction implements FunctionInterface
{
    /**
     * @param callable-string $name
     * @param string[] $aliases
     */
    public function __construct(
        public readonly string $name,
        public array $aliases = [],
    ) {
    }

    public function call(
        array $arguments,
        ViewRenderInterface $render,
        DirectivesInterface $directives,
    ): mixed {
        return \call_user_func_array($this->name, $arguments);
    }
}
