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
use Tuxxedo\Model\Hydration\Hydrator;
use Tuxxedo\Model\Hydration\HydratorInterface;
use Tuxxedo\Model\MetaData\MetaDataInterface;
use Tuxxedo\Model\MetaData\ModelCompositeKeyInterface;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;
use Tuxxedo\Model\MetaData\ModelPrimaryKeyInterface;
use Tuxxedo\Model\MetaData\ModelRelationInterface;
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
    public readonly HydratorInterface $hydrator;

    public function __construct(
        public readonly ConnectionInterface $connection,
        public readonly MetaDataInterface $metaData,
        ?HydratorInterface $hydrator = null,
    ) {
        $this->hydrator = $hydrator ?? new Hydrator($this);
    }

    // @todo Cascade save to loaded relations (depends on dirty tracking)
    /**
     * @template TModel of object
     *
     * @param TModel $model
     * @return TModel
     */
    #[\NoDiscard]
    public function save(
        object $model,
    ): object {
        $metaData = $this->metaData->getModel($model::class);
        $target = $metaData->readonly
            ? $model
            : clone $model;

        return $this->isNewModel($model, $metaData)
            ? $this->insert($target, $metaData)
            : $this->update($target, $metaData);
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
            return PropertyReflector::createFromObject($model, $metaData->key->property)->getValue($model) === null;
        }

        foreach ($metaData->key->properties as $property) {
            if (PropertyReflector::createFromObject($model, $property)->getValue($model) !== null) {
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
            if (\in_array($column->column, $skipColumns, true)) {
                continue;
            }

            $value = PropertyReflector::createFromObject($model, $column->property)->getValue($model);

            if ($value === null && !$column->nullable) {
                throw ModelException::fromNullValueOnNonNullableColumn(
                    modelClass: $metaData->model,
                    property: $column->property,
                );
            }

            if ($value !== null && !\is_scalar($value)) {
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $column->property,
                    actualType: \get_debug_type($value),
                );
            }

            $map[$column->column] = $value;
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
            $metaData->key instanceof ModelPrimaryKeyInterface &&
            $metaData->key->autoIncrement
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
            $metaData->key instanceof ModelPrimaryKeyInterface &&
            $metaData->key->autoIncrement
        ) {
            $id = $this->connection->lastInsertIdAsInt();

            if ($id !== null) {
                if ($metaData->readonly) {
                    return clone (
                        $model,
                        [
                            $metaData->key->property => $id,
                        ],
                    );
                }

                PropertyReflector::createFromObject($model, $metaData->key->property)->setValue($model, $id);
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
            $value = PropertyReflector::createFromObject($model, $metaData->key->property)->getValue($model);

            if (!\is_scalar($value)) {
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $metaData->key->property,
                    actualType: \get_debug_type($value),
                );
            }

            $query->where($metaData->key->column, $value);
        } elseif ($metaData->key instanceof ModelCompositeKeyInterface) {
            foreach (\array_combine($metaData->key->properties, $metaData->key->columns) as $property => $column) {
                $value = PropertyReflector::createFromObject($model, $property)->getValue($model);

                if (!\is_scalar($value)) {
                    throw ModelException::fromPropertyValueMustBeScalar(
                        modelClass: $metaData->model,
                        property: $property,
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
    #[\NoDiscard]
    public function findFirst(
        string $class,
        ?\Closure $criteria = null,
    ): ?object {
        $metaData = $this->metaData->getModel($class);
        $query = $this->connection->select($metaData->table);

        if ($criteria !== null) {
            $criteria($query);
        }

        $model = $query->fetch($class);

        if ($model !== null) {
            $this->hydrator->hydrateRelations($model, $metaData);
        }

        return $model;
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
    #[\NoDiscard]
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
    #[\NoDiscard]
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
    #[\NoDiscard]
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
    ): ?object {
        $metaData = $this->metaData->getModel($class);

        if (!$metaData->key instanceof ModelCompositeKeyInterface) {
            throw ModelException::fromCantFetchWithoutCompositeKey(
                modelClass: $metaData->model,
            );
        }

        return $this->findFirst(
            class: $class,
            criteria: static function (SelectBuilderInterface $builder) use ($criteria, $keys): void {
                if ($criteria !== null) {
                    $criteria($builder);
                }

                foreach ($keys as $column => $value) {
                    $builder->where($column, $value);
                }
            },
        );
    }

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
    ): object {
        return $this->findByCompositeKey($class, $keys, $criteria) ?? throw ModelException::fromModelNotFound(
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
    #[\NoDiscard]
    public function findAll(
        string $class,
        ?\Closure $criteria = null,
    ): \Generator {
        $metaData = $this->metaData->getModel($class);
        $query = $this->connection->select($metaData->table);

        if ($criteria !== null) {
            $criteria($query);
        }

        foreach ($query->fetchAll($class) as $model) {
            $this->hydrator->hydrateRelations($model, $metaData);

            yield $model;
        }
    }

    /**
     * @template TModel of object
     * @param TModel $model
     * @return TModel
     */
    #[\NoDiscard]
    public function refresh(
        object $model,
    ): object {
        $metaData = $this->metaData->getModel($model::class);

        if (!$metaData->key instanceof ModelPrimaryKeyInterface) {
            throw ModelException::fromCantFetchWithoutPrimaryKey(
                modelClass: $metaData->model,
            );
        }

        $value = PropertyReflector::createFromObject($metaData->model, $metaData->key->property)->getValue($model);

        if (!\is_int($value) && !\is_string($value)) {
            throw ModelException::fromPropertyValueMustBeIdentifierType(
                modelClass: $metaData->model,
                property: $metaData->key->property,
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
    #[\NoDiscard]
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
    #[\NoDiscard]
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

    #[\NoDiscard]
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
            $value = PropertyReflector::createFromObject($metaData->model, $metaData->key->property)->getValue($model);

            if (!\is_scalar($value)) {
                throw ModelException::fromPropertyValueMustBeScalar(
                    modelClass: $metaData->model,
                    property: $metaData->key->property,
                    actualType: \get_debug_type($value),
                );
            }

            $query->where(
                $metaData->key->column,
                $value,
            );
        } elseif ($metaData->key instanceof ModelCompositeKeyInterface) {
            foreach (\array_combine($metaData->key->properties, $metaData->key->columns) as $property => $column) {
                $value = PropertyReflector::createFromObject($metaData->model, $property)->getValue($model);

                if (!\is_scalar($value)) {
                    throw ModelException::fromPropertyValueMustBeScalar(
                        modelClass: $metaData->model,
                        property: $property,
                        actualType: \get_debug_type($value),
                    );
                }

                $query->where($column, $value);
            }
        }

        return $query->execute()->affectedRows > 0;
    }

    #[\NoDiscard]
    public function isRelationLoaded(
        object $model,
        string $property,
    ): bool {
        $metaData = $this->metaData->getModel($model::class);
        $this->findRelation($metaData, $property);

        $reflectionProperty = PropertyReflector::createFromObject($model, $property);

        if (!$reflectionProperty->reflector->isInitialized($model)) {
            return false;
        }

        $value = $reflectionProperty->getValue($model);

        if (!\is_object($value)) {
            return true;
        }

        return !(new \ReflectionClass($value))->isUninitializedLazyObject($value);
    }

    #[\NoDiscard]
    public function relation(
        object $model,
        string $property,
    ): ?object {
        $metaData = $this->metaData->getModel($model::class);
        $this->findRelation($metaData, $property);

        $value = PropertyReflector::createFromObject($model, $property)->getValue($model);

        if (!\is_object($value)) {
            return null;
        }

        $reflection = new \ReflectionClass($value);

        if ($reflection->isUninitializedLazyObject($value)) {
            return $reflection->initializeLazyObject($value);
        }

        return $value;
    }

    private function findRelation(
        ModelMetaDataInterface $metaData,
        string $property,
    ): ModelRelationInterface {
        foreach ($metaData->relations as $relation) {
            if ($relation->property === $property) {
                return $relation;
            }
        }

        throw ModelException::fromRelationNotFoundOnModel(
            modelClass: $metaData->model,
            property: $property,
        );
    }
}
