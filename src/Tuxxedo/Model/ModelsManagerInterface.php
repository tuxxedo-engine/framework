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
use Tuxxedo\Database\Query\Builder\ExistsBuilderInterface;
use Tuxxedo\Database\Query\Builder\SelectBuilderInterface;
use Tuxxedo\Model\Hydrator\HydratorInterface;
use Tuxxedo\Model\MetaData\MetaDataInterface;

// @todo Implement Relation<T> wrapper for HasMany and BelongsToMany
// @todo Relation: page(limit:, offset:) immutable, raw pagination
// @todo Relation: totalCount() — separate from Countable, respects filters, ignores pagination
// @todo Relation: where() filter — narrowed where-builders (see criteria narrowing)
// @todo Relation: eager-mode customization triggers lazy refetch
// @todo Support CreatedAt, UpdatedAt & DeletedAt contracts
// @todo Support value hydration and serialization from complex types like Enums, Objects
// @todo Implement a cache strategy
// @todo Dirty models handling
// @todo Readonly support implications for cache strategy and dirty tracking writebacks
// @todo Support recursive save() through loaded relations (depends on dirty tracking)
// @todo Consider findWhere and other shorthands?
// @todo Support with or similar arguments for fetchers on relations to eagerly load them?
// @todo Criteria parameters may need more narrowing to prevent injecting overrides into parts they shouldn't. It should be limited to where builders only
// @todo Consider pagination
#[DefaultImplementation(class: ModelsManager::class, lifecycle: Lifecycle::PERSISTENT)]
interface ModelsManagerInterface
{
    public ConnectionInterface $connection {
        get;
    }

    public MetaDataInterface $metaData {
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
    ): object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectBuilderInterface $builder): void)|null $criteria
     * @return TModel|null
     */
    #[\NoDiscard]
    public function findFirst(
        string $class,
        ?\Closure $criteria = null,
    ): ?object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectBuilderInterface $builder): void)|null $criteria
     * @return TModel
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function fetch(
        string $class,
        ?\Closure $criteria = null,
    ): object;

    /**
     * @template TModel of object
     * @param class-string<TModel> $class
     * @param (\Closure(SelectBuilderInterface $builder): void)|null $criteria
     * @return TModel|null
     */
    #[\NoDiscard]
    public function findByIdentifier(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
    ): ?object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectBuilderInterface $builder): void)|null $criteria
     * @return TModel
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function fetchByIdentifier(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
    ): object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param array<string, int|string> $keys
     * @param (\Closure(SelectBuilderInterface $builder): void)|null $criteria
     * @return TModel|null
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function findByCompositeKey(
        string $class,
        array $keys,
        ?\Closure $criteria = null,
    ): ?object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param array<string, int|string> $keys
     * @param (\Closure(SelectBuilderInterface $builder): void)|null $criteria
     * @return TModel
     *
     * @throws ModelException
     */
    #[\NoDiscard]
    public function fetchByCompositeKey(
        string $class,
        array $keys,
        ?\Closure $criteria = null,
    ): object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectBuilderInterface $builder): void)|null $criteria
     * @return \Generator<TModel>
     */
    #[\NoDiscard]
    public function findAll(
        string $class,
        ?\Closure $criteria = null,
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
     * @param \Closure(ExistsBuilderInterface $builder): void $criteria
     */
    #[\NoDiscard]
    public function exists(
        string $class,
        \Closure $criteria,
    ): bool;

    /**
     * @param class-string $class
     * @param (\Closure(ExistsBuilderInterface $builder): void) $criteria
     */
    #[\NoDiscard]
    public function existsByIdentifier(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
    ): bool;

    #[\NoDiscard]
    public function delete(
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
}
