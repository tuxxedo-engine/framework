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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DefaultInitializer;
use Tuxxedo\Database\ConnectionManagerInterface;
use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Query\Builder\WhereBuilderInterface;
use Tuxxedo\Model\MetaData\MetaDataInterface;

#[DefaultInitializer(
    static function (ContainerInterface $container): ModelsManagerInterface {
        return new ModelsManager(
            connection: $container->resolve(ConnectionManagerInterface::class)->getDefaultConnection(),
            metaData: $container->resolve(MetaDataInterface::class),
        );
    },
)]
class ModelsManager implements ModelsManagerInterface
{
    public function __construct(
        public readonly ConnectionInterface $connection,
        public readonly MetaDataInterface $metaData,
    ) {
    }

    /**
     * @template TModel of object
     *
     * @param TModel $model
     * @return TModel
     */
    public function save(
        object $model,
    ): object {
        // @todo Implement

        return $model;
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(WhereBuilderInterface $builder): void)|null $criteria
     * @return TModel|null
     */
    public function find(
        string $class,
        ?\Closure $criteria = null,
    ): ?object {
        // @todo Implement

        return null;
    }

    /**
     * @template TModel of object
     * @param class-string<TModel> $class
     * @param (\Closure(WhereBuilderInterface $builder): void)|null $criteria
     * @return TModel|null
     */
    public function findByIdentifier(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
    ): ?object {
        // @todo Implement

        return null;
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @param (\Closure(WhereBuilderInterface $builder): void)|null $criteria
     * @return \Generator<TModel>
     */
    public function findAll(
        string $class,
        ?\Closure $criteria = null,
    ): \Generator {
        // @todo Implement

        yield new $class();
    }

    /**
     * @template TModel of object
     * @param TModel $model
     * @return TModel
     */
    public function refresh(
        object $model,
    ): object {
        // @todo Implement

        return $model;
    }

    public function delete(
        object $model,
    ): bool {
        // @todo Implement

        return false;
    }
}
