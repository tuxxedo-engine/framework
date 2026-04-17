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

namespace Unit\Fixtures\Container;

use Tuxxedo\Collection\CollectionInterface;
use Tuxxedo\Container\Resolver\TaggedCollection;

class TaggedServicesCollectionConsumer
{
    /**
     * @param CollectionInterface<int, TaggedServiceInterface> $services
     */
    public function __construct(
        #[TaggedCollection(TaggedServiceInterface::class)] public readonly CollectionInterface $services,
    ) {
    }
}
