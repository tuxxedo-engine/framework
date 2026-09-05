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

use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Attribute\Relation\MorphMany;
use Tuxxedo\Model\Attribute\Relation\MorphOne;
use Tuxxedo\Model\Attribute\Relation\MorphToMany;
use Tuxxedo\Model\MetaData\ModelCompositeKeyInterface;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\RelationInterface;
use Tuxxedo\Reflection\PropertyReflector;

class AggregateSaveOrder
{
    /**
     * @param list<CollectedEntity> $entities
     * @return list<CollectedEntity>
     *
     * @throws ModelException
     */
    public function sort(
        array $entities,
    ): array {
        $this->guardCompositeKeys($entities);

        /** @var array<int, CollectedEntity> $byId */
        $byId = [];
        /** @var array<int, list<int>> $successors */
        $successors = [];
        /** @var array<int, int> $inDegree */
        $inDegree = [];

        foreach ($entities as $collected) {
            $id = \spl_object_id($collected->entity);
            $byId[$id] = $collected;
            $successors[$id] = [];
            $inDegree[$id] = 0;
        }

        foreach ($entities as $collected) {
            $this->collectEdges(
                collected: $collected,
                inDegree: $inDegree,
                successors: $successors,
            );
        }

        return $this->kahn(
            entities: $entities,
            byId: $byId,
            inDegree: $inDegree,
            successors: $successors,
        );
    }

    /**
     * @param list<CollectedEntity> $entities
     *
     * @throws ModelException
     */
    private function guardCompositeKeys(
        array $entities,
    ): void {
        foreach ($entities as $collected) {
            if ($collected->metaData->key instanceof ModelCompositeKeyInterface) {
                throw ModelException::fromAggregateCompositeKeyEntity(
                    modelClass: $collected->metaData->model,
                    path: $collected->path,
                );
            }
        }
    }

    /**
     * @param array<int, int> $inDegree
     * @param array<int, list<int>> $successors
     */
    private function collectEdges(
        CollectedEntity $collected,
        array &$inDegree,
        array &$successors,
    ): void {
        $entity = $collected->entity;

        foreach ($collected->metaData->relations as $relation) {
            if (!RelationLoadState::isLoaded($entity, $relation)) {
                continue;
            }

            $value = PropertyReflector::createFromObject($entity, $relation->property)->getValue($entity);
            $attribute = $relation->attribute;

            if ($attribute instanceof BelongsTo) {
                if (\is_object($value)) {
                    $this->addEdge($inDegree, $successors, from: $value, to: $entity);
                }

                continue;
            }

            if ($attribute instanceof HasOne || $attribute instanceof MorphOne) {
                if (\is_object($value)) {
                    $this->addEdge($inDegree, $successors, from: $entity, to: $value);
                }

                continue;
            }

            if (
                $attribute instanceof HasMany ||
                $attribute instanceof MorphMany
            ) {
                if ($value instanceof RelationInterface) {
                    foreach ($value as $item) {
                        $this->addEdge($inDegree, $successors, from: $entity, to: $item);
                    }
                }

                continue;
            }

            if (
                $attribute instanceof BelongsToMany ||
                $attribute instanceof MorphToMany
            ) {
                if ($value instanceof RelationInterface) {
                    foreach ($value as $item) {
                        $this->addEdge($inDegree, $successors, from: $item, to: $entity);
                    }
                }
            }
        }

        foreach ($collected->metaData->morphToRelations as $morphTo) {
            if (!RelationLoadState::isLoaded($entity, $morphTo)) {
                continue;
            }

            $value = PropertyReflector::createFromObject($entity, $morphTo->property)->getValue($entity);

            if (\is_object($value)) {
                $this->addEdge($inDegree, $successors, from: $value, to: $entity);
            }
        }
    }

    /**
     * @param array<int, int> $inDegree
     * @param array<int, list<int>> $successors
     */
    private function addEdge(
        array &$inDegree,
        array &$successors,
        object $from,
        object $to,
    ): void {
        $fromId = \spl_object_id($from);
        $toId = \spl_object_id($to);

        if (!isset($inDegree[$fromId]) || !isset($inDegree[$toId])) {
            return; // @codeCoverageIgnore
        }

        if ($fromId === $toId) {
            return;
        }

        if (\in_array($toId, $successors[$fromId], true)) {
            return;
        }

        $successors[$fromId][] = $toId;
        $inDegree[$toId]++;
    }

    /**
     * @param list<CollectedEntity> $entities
     * @param array<int, CollectedEntity> $byId
     * @param array<int, int> $inDegree
     * @param array<int, list<int>> $successors
     * @return list<CollectedEntity>
     *
     * @throws ModelException
     */
    private function kahn(
        array $entities,
        array $byId,
        array $inDegree,
        array $successors,
    ): array {
        /** @var list<int> $queue */
        $queue = [];

        foreach ($inDegree as $id => $degree) {
            if ($degree === 0) {
                $queue[] = $id;
            }
        }

        /** @var list<CollectedEntity> $sorted */
        $sorted = [];

        while (\sizeof($queue) > 0) {
            $id = \array_shift($queue);
            $sorted[] = $byId[$id];

            foreach ($successors[$id] as $successorId) {
                $inDegree[$successorId]--;

                if ($inDegree[$successorId] === 0) {
                    $queue[] = $successorId;
                }
            }
        }

        if (\sizeof($sorted) !== \sizeof($entities)) {
            throw ModelException::fromAggregateEntityCycle();
        }

        return $sorted;
    }
}
