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

use Tuxxedo\Model\MetaData\ModelMetaDataInterface;
use Tuxxedo\Reflection\PropertyReflector;

class DirtyTracker implements DirtyTrackerInterface
{
    /**
     * @var \WeakMap<object, array<string, mixed>>
     */
    private \WeakMap $snapshots;

    public function __construct()
    {
        $this->snapshots = new \WeakMap();
    }

    public function recordSnapshot(
        object $model,
        ModelMetaDataInterface $metaData,
    ): void {
        $this->snapshots[$model] = $this->captureColumnValues($model, $metaData);
    }

    public function forgetSnapshot(
        object $model,
    ): void {
        unset($this->snapshots[$model]);
    }

    #[\NoDiscard]
    public function hasSnapshot(
        object $model,
    ): bool {
        return isset($this->snapshots[$model]);
    }

    /**
     * @return array<string, mixed>
     */
    #[\NoDiscard]
    public function getDirtyColumns(
        object $model,
        ModelMetaDataInterface $metaData,
    ): array {
        $current = $this->captureColumnValues($model, $metaData);
        $snapshot = $this->snapshots[$model] ?? null;

        if ($snapshot === null) {
            return $current;
        }

        $dirty = [];

        foreach ($current as $column => $value) {
            if (!\array_key_exists($column, $snapshot) || $snapshot[$column] !== $value) {
                $dirty[$column] = $value;
            }
        }

        return $dirty;
    }

    #[\NoDiscard]
    public function isDirty(
        object $model,
        ModelMetaDataInterface $metaData,
    ): bool {
        $snapshot = $this->snapshots[$model] ?? null;

        if ($snapshot === null) {
            return true;
        }

        foreach ($metaData->columns as $column) {
            $value = $this->readValue($model, $column->property);

            if (!\array_key_exists($column->column, $snapshot) || $snapshot[$column->column] !== $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws ModelException
     */
    #[\NoDiscard]
    public function isDirtyProperty(
        object $model,
        ModelMetaDataInterface $metaData,
        string $property,
    ): bool {
        $snapshot = $this->snapshots[$model] ?? null;

        if ($snapshot === null) {
            return true;
        }

        foreach ($metaData->columns as $column) {
            if ($column->property !== $property) {
                continue;
            }

            $value = $this->readValue($model, $property);

            return !\array_key_exists($column->column, $snapshot) || $snapshot[$column->column] !== $value;
        }

        throw ModelException::fromPropertyIsNotAColumn(
            modelClass: $metaData->model,
            property: $property,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function captureColumnValues(
        object $model,
        ModelMetaDataInterface $metaData,
    ): array {
        $values = [];

        foreach ($metaData->columns as $column) {
            $values[$column->column] = $this->readValue($model, $column->property);
        }

        return $values;
    }

    private function readValue(
        object $model,
        string $property,
    ): mixed {
        return PropertyReflector::createFromObject($model, $property)->getValue($model);
    }
}
