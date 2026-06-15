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
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Query\Statement\ExistsStatementInterface;
use Tuxxedo\Database\Query\Statement\SelectStatementInterface;
use Tuxxedo\Model\Attribute\ColumnInterface;
use Tuxxedo\Model\Behavior\BehaviorInterface;
use Tuxxedo\Model\Hydrator\Coercer\CoercerInterface;
use Tuxxedo\Model\Hydrator\HydratorInterface;
use Tuxxedo\Model\MetaData\MetaDataInterface;

// @todo Implement a cache strategy
// @todo Consider findWhere and other shorthands?
// @todo Support with or similar arguments for fetchers on relations to eagerly load them?
// @todo Criteria parameters may need more narrowing to prevent injecting overrides into parts they shouldn't. It should be limited to where builders only
// @todo Consider pagination
// @todo Repository foundation
#[DefaultImplementation(class: ModelsManager::class, lifecycle: Lifecycle::PERSISTENT)]
interface ModelsManagerInterface
{
    public ConnectionInterface $connection {
        get;
    }

    public MetaDataInterface $metaData {
        get;
    }

    public DirtyTrackerInterface $dirtyTracker {
        get;
    }

    public HydratorInterface $hydrator {
        get;
    }

    /**
     * @template TModel of object
     *
     * @param TModel $model
     * @return TModel
     */
    #[\NoDiscard]
    public function save(
        object $model,
        bool $forceMaterialize = false,
    ): object;

    public function getCoercerFor(
        ColumnInterface $attribute,
    ): ?CoercerInterface;

    /**
     * @template TBehavior of BehaviorInterface
     *
     * @param class-string<TBehavior> $behaviorClass
     * @return TBehavior
     */
    public function getBehaviorFor(
        string $behaviorClass,
    ): BehaviorInterface;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectStatementInterface $builder): void)|null $criteria
     * @return TModel|null
     */
    #[\NoDiscard]
    public function findFirst(
        string $class,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
    ): ?object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectStatementInterface $builder): void)|null $criteria
     * @return TModel
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function fetch(
        string $class,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
    ): object;

    /**
     * @template TModel of object
     * @param class-string<TModel> $class
     * @param (\Closure(SelectStatementInterface $builder): void)|null $criteria
     * @return TModel|null
     */
    #[\NoDiscard]
    public function findByIdentifier(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
    ): ?object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectStatementInterface $builder): void)|null $criteria
     * @return TModel
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function fetchByIdentifier(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
    ): object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param array<string, int|string> $keys
     * @param (\Closure(SelectStatementInterface $builder): void)|null $criteria
     * @return TModel|null
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function findByCompositeKey(
        string $class,
        array $keys,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
    ): ?object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param array<string, int|string> $keys
     * @param (\Closure(SelectStatementInterface $builder): void)|null $criteria
     * @return TModel
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function fetchByCompositeKey(
        string $class,
        array $keys,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
    ): object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectStatementInterface $builder): void)|null $criteria
     * @return \Generator<int, TModel>
     */
    #[\NoDiscard]
    public function findAll(
        string $class,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
    ): \Generator;

    /**
     * @template TModel of object
     * @param TModel $model
     * @return TModel
     */
    #[\NoDiscard]
    public function refresh(
        object $model,
    ): object;

    /**
     * @param class-string $class
     * @param \Closure(ExistsStatementInterface $builder): void $criteria
     */
    #[\NoDiscard]
    public function exists(
        string $class,
        \Closure $criteria,
        bool $includeDeleted = false,
    ): bool;

    /**
     * @param class-string $class
     * @param (\Closure(ExistsStatementInterface $builder): void) $criteria
     */
    #[\NoDiscard]
    public function existsByIdentifier(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
    ): bool;

    #[\NoDiscard]
    public function delete(
        object $model,
    ): bool;

    #[\NoDiscard]
    public function forceDelete(
        object $model,
    ): bool;

    /**
     * @throws ModelException
     */
    #[\NoDiscard]
    public function isRelationLoaded(
        object $model,
        string $property,
    ): bool;

    /**
     * @throws ModelException
     */
    #[\NoDiscard]
    public function relation(
        object $model,
        string $property,
    ): ?object;

    public function trackAsExisting(
        object $model,
    ): void;
}
