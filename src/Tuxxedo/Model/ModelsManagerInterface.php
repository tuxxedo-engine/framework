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
use Tuxxedo\Database\Query\Statement\CountStatementInterface;
use Tuxxedo\Database\Query\Statement\ExistsStatementInterface;
use Tuxxedo\Database\Query\Statement\SelectStatementInterface;
use Tuxxedo\Database\Query\Statement\Table\CreateTableStatementInterface;
use Tuxxedo\Database\Query\Statement\WhereStatementInterface;
use Tuxxedo\Model\Attribute\ColumnInterface;
use Tuxxedo\Model\Behavior\BehaviorInterface;
use Tuxxedo\Model\Hydrator\Coercer\CoercerInterface;
use Tuxxedo\Model\Hydrator\HydratorInterface;
use Tuxxedo\Model\MetaData\MetaDataInterface;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;

/**
 * @todo Consider supporting MorphTo/MorphMany
 */
#[DefaultImplementation(class: ModelsManager::class, lifecycle: Lifecycle::SINGLETON)]
interface ModelsManagerInterface
{
    final public const int DEFAULT_EAGER_CHUNK_SIZE = 100;

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
     *
     * @todo `save()` validates the passed model's own columns and property-attribute rules but does NOT recurse into related entities reached through relation attributes — they're skipped by Validator to avoid triggering lazy-proxy hydration and bidirectional-cycle recursion. Each related entity must be saved (and thus validated) independently. Revisit when a real "validate whole aggregate atomically" use case surfaces.
     */
    #[\NoDiscard]
    public function save(
        object $model,
        bool $forceMaterialize = false,
        bool $skipValidation = false,
    ): object;

    /**
     * @param class-string $modelClass
     */
    #[\NoDiscard]
    public function createTable(
        string $modelClass,
    ): CreateTableStatementInterface;

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
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null>|null $with
     * @return TModel|null
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function findFirst(
        string $class,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): ?object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null>|null $with
     * @return TModel
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function fetch(
        string $class,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): object;

    /**
     * @template TModel of object
     * @param class-string<TModel> $class
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null>|null $with
     * @return TModel|null
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function findById(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): ?object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null>|null $with
     * @return TModel
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function fetchById(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param array<string, int|string> $keys
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null>|null $with
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
        ?array $with = null,
    ): ?object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param array<string, int|string> $keys
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null>|null $with
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
        ?array $with = null,
    ): object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectStatementInterface $statement): void)|null $criteria
     * @param array<string, (\Closure(Relation<object>): Relation<object>)|null>|null $with
     * @return \Generator<int, TModel>
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function findAll(
        string $class,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
        int $chunkSize = self::DEFAULT_EAGER_CHUNK_SIZE,
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
     * @param \Closure(ExistsStatementInterface $statement): void $criteria
     */
    #[\NoDiscard]
    public function exists(
        string $class,
        \Closure $criteria,
        bool $includeDeleted = false,
    ): bool;

    /**
     * @param class-string $class
     * @param (\Closure(ExistsStatementInterface $statement): void) $criteria
     */
    #[\NoDiscard]
    public function existsById(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
    ): bool;

    /**
     * @param class-string $class
     * @param \Closure(CountStatementInterface $statement): void $criteria
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function count(
        string $class,
        \Closure $criteria,
        bool $includeDeleted = false,
    ): int;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @return Query<TModel>
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function query(
        string $class,
        bool $includeDeleted = false,
    ): Query;

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

    public function applySoftDeleteFilter(
        WhereStatementInterface $query,
        ModelMetaDataInterface $metaData,
    ): void;
}
