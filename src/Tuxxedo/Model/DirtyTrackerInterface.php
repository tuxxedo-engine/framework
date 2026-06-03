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

namespace Tuxxedo\Model;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;

// @todo trackAsExisting() support for manual dirty tracking of custom created models
#[DefaultImplementation(class: DirtyTracker::class, lifecycle: Lifecycle::PERSISTENT)]
interface DirtyTrackerInterface
{
    public function recordSnapshot(
        object $model,
        ModelMetaDataInterface $metaData,
    ): void;

    public function forgetSnapshot(
        object $model,
    ): void;

    #[\NoDiscard]
    public function hasSnapshot(
        object $model,
    ): bool;

    /**
     * @return array<string, mixed>
     */
    #[\NoDiscard]
    public function getDirtyColumns(
        object $model,
        ModelMetaDataInterface $metaData,
    ): array;

    #[\NoDiscard]
    public function isDirty(
        object $model,
        ModelMetaDataInterface $metaData,
    ): bool;

    /**
     * @throws ModelException
     */
    #[\NoDiscard]
    public function isDirtyProperty(
        object $model,
        ModelMetaDataInterface $metaData,
        string $property,
    ): bool;
}
