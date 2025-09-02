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

namespace Tuxxedo\View\Lumi\Runtime\Filter;

use Tuxxedo\View\Lumi\Runtime\Directive\DirectivesInterface;

interface FilterProviderInterface
{
    /**
     * @return \Generator<array{0: string, 1: \Closure(mixed $value, DirectivesInterface $directives): mixed}>
     */
    public function export(): \Generator;
}
