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
use Tuxxedo\Database\Query\Builder\ExistsBuilderInterface;
use Tuxxedo\Database\Query\Builder\SelectBuilderInterface;
use Tuxxedo\Model\MetaData\MetaDataInterface;
use Tuxxedo\Model\MetaData\ModelCompositeKeyInterface;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;
use Tuxxedo\Model\MetaData\ModelPrimaryKeyInterface;
use Tuxxedo\Reflection\PropertyReflector;

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
        $metaData = $this->metaData->getModel($model::class);

        return $this->isNewModel($model, $metaData)
            ? $this->insert($model, $metaData)
            : $this->update($model, $metaData);
    }

    private function isNewModel(
        object $model,
        ModelMetaDataInterface $metaData,
    ): bool {
        if ($metaData->key === null) {
            throw ModelException::fromNoPrimaryKeyOrCompositeKey(
                modelClass: $metaData->model,
            );
        }

        if ($metaData->key instanceof ModelPrimaryKeyInterface) {
            return PropertyReflector::createFromObject($model, $metaData->key->column)->getValue($model) === null;
        }

        foreach ($metaData->key->columns as $column) {
            if (PropertyReflector::createFromObject($model, $column)->getValue($model) !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string[] $skipColumns
     * @return array<string, scalar|null>
     */
    private function buildColumnMap(
        object $model,
        ModelMetaDataInterface $metaData,
        array $skipColumns = [],
    ): array {
        $map = [];

        foreach ($metaData->columns as $column) {
            if (\in_array($column->name, $skipColumns, true)) {
                continue;
            }

            $value = PropertyReflector::createFromObject($model, $column->name)->getValue($model);

            if ($value === null && !$column->nullable) {
                throw ModelException::fromNullValueOnNonNullableColumn(
                    modelClass: $metaData->model,
                    property: $column->name,
                );
            }

            if ($value !== null && !\is_scalar($value)) {
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $column->name,
                    actualType: \get_debug_type($value),
                );
            }

            $map[$column->name] = $value;
        }

        return $map;
    }

    /**
     * @template TModel of object
     *
     * @param TModel $model
     * @return TModel
     */
    private function insert(
        object $model,
        ModelMetaDataInterface $metaData,
    ): object {
        $skipColumns = [];

        if (
            $metaData->key instanceof ModelPrimaryKeyInterface
            && $metaData->key->autoIncrement
        ) {
            $skipColumns[] = $metaData->key->column;
        }

        $columns = $this->buildColumnMap($model, $metaData, $skipColumns);
        $query = $this->connection->insert($metaData->table);

        foreach ($columns as $column => $value) {
            $query->set($column, $value);
        }

        $query->execute();

        if (
            $metaData->key instanceof ModelPrimaryKeyInterface
            && $metaData->key->autoIncrement
        ) {
            $id = $this->connection->lastInsertIdAsInt();

            if ($id !== null) {
                PropertyReflector::createFromObject($model, $metaData->key->column)->setValue($model, $id);
            }
        }

        return $model;
    }

    /**
     * @template TModel of object
     *
     * @param TModel $model
     * @return TModel
     */
    private function update(
        object $model,
        ModelMetaDataInterface $metaData,
    ): object {
        $skipColumns = [];

        if ($metaData->key instanceof ModelPrimaryKeyInterface) {
            $skipColumns[] = $metaData->key->column;
        } elseif ($metaData->key instanceof ModelCompositeKeyInterface) {
            foreach ($metaData->key->columns as $column) {
                $skipColumns[] = $column;
            }
        }

        $columns = $this->buildColumnMap($model, $metaData, $skipColumns);
        $query = $this->connection->update($metaData->table);

        foreach ($columns as $column => $value) {
            $query->set($column, $value);
        }

        if ($metaData->key instanceof ModelPrimaryKeyInterface) {
            $value = PropertyReflector::createFromObject($model, $metaData->key->column)->getValue($model);

            if (!\is_scalar($value)) {
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $metaData->key->column,
                    actualType: \get_debug_type($value),
                );
            }

            $query->where($metaData->key->column, $value);
        } elseif ($metaData->key instanceof ModelCompositeKeyInterface) {
            foreach ($metaData->key->columns as $column) {
                $value = PropertyReflector::createFromObject($model, $column)->getValue($model);

                if (!\is_scalar($value)) {
                    throw ModelException::fromPropertyValueMustBeScalar(
                        modelClass: $metaData->model,
                        property: $column,
                        actualType: \get_debug_type($value),
                    );
                }

                $query->where($column, $value);
            }
        }

        $query->execute();

        return $model;
    }

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
    ): ?object {
        $query = $this->connection->select($this->metaData->getModel($class)->table);

        if ($criteria !== null) {
            $criteria($query);
        }

        return $query->fetch($class);
    }

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
    ): object {
        return $this->findFirst($class, $criteria) ?? throw ModelException::fromModelNotFound(
            modelClass: $class,
        );
    }

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
    ): ?object {
        $metaData = $this->metaData->getModel($class);

        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
        }

        return $this->findFirst(
            class: $class,
            criteria: static function (SelectBuilderInterface $builder) use ($criteria, $metaData, $id): void {
                if ($criteria !== null) {
                    $criteria($builder);
                }

                $builder->where($metaData->key->column, $id);
            },
        );
    }

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
    ): object {
        return $this->findByIdentifier($class, $id, $criteria) ?? throw ModelException::fromModelNotFound(
            modelClass: $class,
        );
    }

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
    ): \Generator {
        $query = $this->connection->select($this->metaData->getModel($class)->table);

        if ($criteria !== null) {
            $criteria($query);
        }

        yield from $query->fetchAll($class);
    }

    /**
     * @template TModel of object
     * @param TModel $model
     * @return TModel
     */
    public function refresh(
        object $model,
    ): object {
        $metaData = $this->metaData->getModel($model::class);

        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
        }

        $value = PropertyReflector::createFromObject($metaData->model, $metaData->key->column)->getValue($model);

        if (!\is_int($value) && !\is_string($value)) {
            throw ModelException::fromPropertyValueMustBeIdentifierType(
                modelClass: $metaData->model,
                property: $metaData->key->column,
                actualType: \get_debug_type($value),
            );
        }

        $fresh = $this->findByIdentifier($model::class, $value);

        if ($fresh === null) {
            throw ModelException::fromModelNoLongerExists(
                modelClass: $metaData->model,
            );
        }

        return $fresh;
    }

    /**
     * @param class-string $class
     * @param \Closure(ExistsBuilderInterface $builder): void $criteria
     */
    public function exists(
        string $class,
        \Closure $criteria,
    ): bool {
        $query = $this->connection->exists($this->metaData->getModel($class)->table);

        $criteria($query);

        return $query->exists();
    }

    /**
     * @param class-string $class
     * @param (\Closure(ExistsBuilderInterface $builder): void) $criteria
     */
    public function existsByIdentifier(
        string $class,
        int|string $id,
        ?\Closure $criteria = null,
    ): bool {
        $metaData = $this->metaData->getModel($class);

        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
        }

        return $this->exists(
            class: $class,
            criteria: static function (ExistsBuilderInterface $builder) use ($criteria, $metaData, $id): void {
                if ($criteria !== null) {
                    $criteria($builder);
                }

                $builder->where($metaData->key->column, $id);
            },
        );
    }

    public function delete(
        object $model,
    ): bool {
        $metaData = $this->metaData->getModel($model::class);

        if ($metaData->key === null) {
            throw ModelException::fromNoPrimaryKeyOrCompositeKey(
                modelClass: $metaData->model,
            );
        }

        $query = $this->connection->delete($metaData->table);

        if ($metaData->key instanceof ModelPrimaryKeyInterface) {
            $value = PropertyReflector::createFromObject($metaData->model, $metaData->key->column)->getValue($model);

            if (!\is_scalar($value)) {
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $metaData->key->column,
                    actualType: \get_debug_type($value),
                );
            }

            $query->where(
                $metaData->key->column,
                $value,
            );
        } elseif ($metaData->key instanceof ModelCompositeKeyInterface) {
            foreach ($metaData->key->columns as $column) {
                $value = PropertyReflector::createFromObject($metaData->model, $column)->getValue($model);

                if (!\is_scalar($value)) {
                    throw ModelException::fromPropertyValueMustBeScalar(
                        modelClass: $metaData->model,
                        property: $column,
                        actualType: \get_debug_type($value),
                    );
                }

                $query->where(
                    $column,
                    $value,
                );
            }
        }

        return $query->execute()->affectedRows > 0;
    }
}
