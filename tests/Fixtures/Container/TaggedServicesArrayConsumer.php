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

namespace Fixtures\Container;

use Tuxxedo\Container\Resolver\Tagged;

class TaggedServicesArrayConsumer
{
    /**
     * @param TaggedServiceInterface[] $services
     */
    public function __construct(
        #[Tagged(TaggedServiceInterface::class)] public readonly array $services,
    ) {
    }
}
