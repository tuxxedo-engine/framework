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

namespace Support\Model;

use Tuxxedo\Database\Driver\ConnectionInterface;
use Tuxxedo\Database\Query\Statement\Table\CreateTableStatementInterface;
use Tuxxedo\Database\Query\Statement\WhereStatementInterface;
use Tuxxedo\Model\Attribute\ColumnInterface;
use Tuxxedo\Model\Behavior\BehaviorInterface;
use Tuxxedo\Model\DirtyTrackerInterface;
use Tuxxedo\Model\Hydrator\Coercer\CoercerInterface;
use Tuxxedo\Model\Hydrator\HydratorInterface;
use Tuxxedo\Model\MetaData\MetaDataInterface;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;
use Tuxxedo\Model\ModelsManagerInterface;
use Tuxxedo\Model\Query;

class StubModelsManager implements ModelsManagerInterface
{
    public ConnectionInterface $connection {
        get {
            throw new \LogicException('Not implemented in stub');
        }
    }

    public MetaDataInterface $metaData {
        get {
            throw new \LogicException('Not implemented in stub');
        }
    }

    public DirtyTrackerInterface $dirtyTracker {
        get {
            throw new \LogicException('Not implemented in stub');
        }
    }

    public HydratorInterface $hydrator {
        get {
            throw new \LogicException('Not implemented in stub');
        }
    }

    /**
     * @var list<array{class: string, id: int|string}>
     */
    public array $findByIdCalls = [];

    public function __construct(
        public ?object $findByIdReturn = null,
    ) {
    }

    /**
     * @template TModel of object
     *
     * @param class-string<TModel> $class
     * @return TModel|null
     */
    public function findById(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): ?object {
        $this->findByIdCalls[] = [
            'class' => $class,
            'id' => $id,
        ];

        /** @var TModel|null */
        return $this->findByIdReturn;
    }

    public function save(
        object $model,
        bool $forceMaterialize = false,
        bool $skipValidation = false,
    ): object {
        throw new \LogicException('Not implemented in stub');
    }

    public function createTable(
        string $modelClass,
    ): CreateTableStatementInterface {
        throw new \LogicException('Not implemented in stub');
    }

    public function getCoercerFor(
        ColumnInterface $attribute,
    ): ?CoercerInterface {
        throw new \LogicException('Not implemented in stub');
    }

    public function getBehaviorFor(
        string $behaviorClass,
    ): BehaviorInterface {
        throw new \LogicException('Not implemented in stub');
    }

    public function findFirst(
        string $class,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): ?object {
        throw new \LogicException('Not implemented in stub');
    }

    public function fetch(
        string $class,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): object {
        throw new \LogicException('Not implemented in stub');
    }

    public function fetchById(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): object {
        throw new \LogicException('Not implemented in stub');
    }

    public function findByCompositeKey(
        string $class,
        array $keys,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): ?object {
        throw new \LogicException('Not implemented in stub');
    }

    public function fetchByCompositeKey(
        string $class,
        array $keys,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
    ): object {
        throw new \LogicException('Not implemented in stub');
    }

    public function findAll(
        string $class,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
        ?array $with = null,
        int $chunkSize = self::DEFAULT_EAGER_CHUNK_SIZE,
    ): \Generator {
        throw new \LogicException('Not implemented in stub');
    }

    public function refresh(
        object $model,
    ): object {
        throw new \LogicException('Not implemented in stub');
    }

    public function exists(
        string $class,
        \Closure $criteria,
        bool $includeDeleted = false,
    ): bool {
        throw new \LogicException('Not implemented in stub');
    }

    public function existsById(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
        bool $includeDeleted = false,
    ): bool {
        throw new \LogicException('Not implemented in stub');
    }

    public function count(
        string $class,
        \Closure $criteria,
        bool $includeDeleted = false,
    ): int {
        throw new \LogicException('Not implemented in stub');
    }

    public function query(
        string $class,
        bool $includeDeleted = false,
    ): Query {
        throw new \LogicException('Not implemented in stub');
    }

    public function delete(
        object $model,
    ): bool {
        throw new \LogicException('Not implemented in stub');
    }

    public function forceDelete(
        object $model,
    ): bool {
        throw new \LogicException('Not implemented in stub');
    }

    public function isRelationLoaded(
        object $model,
        string $property,
    ): bool {
        throw new \LogicException('Not implemented in stub');
    }

    public function relation(
        object $model,
        string $property,
    ): ?object {
        throw new \LogicException('Not implemented in stub');
    }

    public function trackAsExisting(
        object $model,
    ): void {
        throw new \LogicException('Not implemented in stub');
    }

    public function applySoftDeleteFilter(
        WhereStatementInterface $query,
        ModelMetaDataInterface $metaData,
    ): void {
        throw new \LogicException('Not implemented in stub');
    }
}
