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

namespace Tuxxedo\Model\MetaData;

use Tuxxedo\Model\Behavior\BehaviorInterface;

readonly class ModelMetaData implements ModelMetaDataInterface
{
    /**
     * @param class-string $model
     * @param non-empty-array<ModelColumnInterface> $columns
     * @param ModelIdentifierInterface[] $identifiers
     * @param ModelRelationInterface[] $relations
     * @param array<string, class-string<BehaviorInterface>> $behaviors
     */
    public function __construct(
        public string $model,
        public string $table,
        public ModelPrimaryKeyInterface|ModelCompositeKeyInterface|null $key,
        public array $columns,
        public array $identifiers,
        public bool $readonly,
        public array $relations = [],
        public array $behaviors = [],
    ) {
    }

    /**
     * @template TBehavior of BehaviorInterface
     *
     * @param class-string<TBehavior> $behavior
     * @return array<string, class-string<TBehavior&BehaviorInterface>>
     */
    public function behaviorsOf(
        string $behavior,
    ): array {
        $result = [];

        foreach ($this->behaviors as $property => $behaviorClass) {
            if (\is_a($behaviorClass, $behavior, true)) {
                $result[$property] = $behaviorClass;
            }
        }

        return $result;
    }

    public function columnFor(
        string $property,
    ): ?ModelColumnInterface {
        foreach ($this->columns as $column) {
            if ($column->property === $property) {
                return $column;
            }
        }

        return null;
    }
}
