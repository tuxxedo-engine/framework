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
use Tuxxedo\Model\MetaData\MetaDataInterface;

// @todo Support relations
// @todo Support CreatedAt, UpdatedAt & DeletedAt contracts
// @todo Support value hydration and serialization from complex types like Enums, Objects
// @todo Should save() return a boolean? Given it doesn't clone or refresh the model but modifies the input, it might make sense
// @todo Implement a cache strategy
#[DefaultImplementation(class: ModelsManager::class, lifecycle: Lifecycle::PERSISTENT)]
interface ModelsManagerInterface
{
    public ConnectionInterface $connection {
        get;
    }

    public MetaDataInterface $metaData {
        get;
    }

    /**
     * @template TModel of object
     *
     * @param TModel $model
     * @return TModel
     */
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
    public function fetchByIdentifier(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
    ): object;

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(SelectBuilderInterface $builder): void)|null $criteria
     * @return \Generator<TModel>
     */
    public function findAll(
        string $class,
        ?\Closure $criteria = null,
    ): \Generator;

    /**
     * @template TModel of object
     * @param TModel $model
     * @return TModel
     */
    public function refresh(
        object $model,
    ): object;

    /**
     * @param class-string $class
     * @param \Closure(ExistsBuilderInterface $builder): void $criteria
     */
    public function exists(
        string $class,
        \Closure $criteria,
    ): bool;

    /**
     * @param class-string $class
     * @param (\Closure(ExistsBuilderInterface $builder): void) $criteria
     */
    public function existsByIdentifier(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
    ): bool;

    public function delete(
        object $model,
    ): bool;
}
