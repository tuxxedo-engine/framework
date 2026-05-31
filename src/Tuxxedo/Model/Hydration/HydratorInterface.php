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

namespace Tuxxedo\Model\Hydration;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;

#[DefaultImplementation(class: Hydrator::class, lifecycle: Lifecycle::PERSISTENT)]
interface HydratorInterface
{
    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $className
     * @param array<string, mixed> $row
     * @return TModel
     */
    public function hydrateFromRow(
        string $className,
        array $row,
        ModelMetaDataInterface $metaData,
    ): object;

    public function hydrateRelations(
        object $model,
        ModelMetaDataInterface $metaData,
    ): void;
}
