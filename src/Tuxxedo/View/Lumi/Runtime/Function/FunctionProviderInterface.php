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

namespace Tuxxedo\View\Lumi\Runtime\Function;

use Tuxxedo\View\Lumi\Runtime\Directive\DirectivesInterface;
use Tuxxedo\View\ViewRenderInterface;

interface FunctionProviderInterface
{
    /**
     * @return \Generator<array{0: string, 1: \Closure(array<mixed> $arguments, ViewRenderInterface $render, DirectivesInterface $directives): mixed}>
     */
    public function export(): \Generator;
}
