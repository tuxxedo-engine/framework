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

namespace Tuxxedo\Model\Aggregate;

use Tuxxedo\Model\MetaData\MetaDataInterface;
use Tuxxedo\Model\RelationInterface;
use Tuxxedo\Reflection\PropertyReflector;

class AggregateEntityCollector
{
    public function __construct(
        private readonly MetaDataInterface $metaData,
    ) {
    }

    /**
     * @return list<CollectedEntity>
     */
    public function collect(
        object $root,
    ): array {
        $collected = [];
        /** @var \WeakMap<object, true> $visited */
        $visited = new \WeakMap();

        $this->walk(
            entity: $root,
            path: '',
            collected: $collected,
            visited: $visited,
        );

        return $collected;
    }

    /**
     * @param list<CollectedEntity> $collected
     * @param \WeakMap<object, true> $visited
     */
    private function walk(
        object $entity,
        string $path,
        array &$collected,
        \WeakMap $visited,
    ): void {
        if (isset($visited[$entity])) {
            return;
        }

        $visited[$entity] = true;

        $entityMetaData = $this->metaData->getModel($entity::class);
        $collected[] = new CollectedEntity(
            path: $path,
            entity: $entity,
            metaData: $entityMetaData,
        );

        foreach ($entityMetaData->relations as $relation) {
            if (!RelationLoadState::isLoaded($entity, $relation)) {
                continue;
            }

            $value = PropertyReflector::createFromObject($entity, $relation->property)->getValue($entity);

            if ($value instanceof RelationInterface) {
                $index = 0;

                foreach ($value as $item) {
                    $this->walk(
                        entity: $item,
                        path: $this->extendPath($path, $relation->property . '.' . $index),
                        collected: $collected,
                        visited: $visited,
                    );

                    $index++;
                }

                continue;
            }

            if (\is_object($value)) {
                $this->walk(
                    entity: $value,
                    path: $this->extendPath($path, $relation->property),
                    collected: $collected,
                    visited: $visited,
                );
            }
        }

        foreach ($entityMetaData->morphToRelations as $morphTo) {
            if (!RelationLoadState::isLoaded($entity, $morphTo)) {
                continue;
            }

            $value = PropertyReflector::createFromObject($entity, $morphTo->property)->getValue($entity);

            if (\is_object($value)) {
                $this->walk(
                    entity: $value,
                    path: $this->extendPath($path, $morphTo->property),
                    collected: $collected,
                    visited: $visited,
                );
            }
        }
    }

    private function extendPath(
        string $parent,
        string $segment,
    ): string {
        return $parent === ''
            ? $segment
            : $parent . '.' . $segment;
    }
}
