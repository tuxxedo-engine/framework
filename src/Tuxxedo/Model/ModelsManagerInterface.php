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
use Tuxxedo\Database\Query\Builder\SelectBuilderInterface;

// @todo Support relations
// @todo Support CreatedAt, UpdatedAt & DeletedAt contracts
// @todo PropertyReflection for attributes
#[DefaultImplementation(class: ModelsManager::class, lifecycle: Lifecycle::PERSISTENT)]
interface ModelsManagerInterface
{
    public ConnectionInterface $connection {
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
    public function find(
        string $class,
        ?\Closure $criteria = null,
    ): ?object;

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

    public function delete(
        object $model,
    ): bool;
}
