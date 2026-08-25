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

namespace Fixture\Router\RouteDiscoverer\Support;

use Tuxxedo\Router\Attribute\ArgumentConsumerInterface;

#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
readonly class ConsumerAttribute implements ArgumentConsumerInterface
{
    public array $routeArguments;

    /**
     * @param list<string> $arguments
     */
    public function __construct(
        private array $arguments,
    ) {
        $this->routeArguments = $this->arguments;
    }
}
