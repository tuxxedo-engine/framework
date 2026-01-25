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

use Tuxxedo\Container\ContainerInterface;

interface FilterProviderInterface
{
    /**
     * @return \Generator<FilterInterface>
     */
    public function export(
        ContainerInterface $container,
    ): \Generator;
}
