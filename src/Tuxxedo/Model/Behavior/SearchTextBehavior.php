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

namespace Tuxxedo\Model\Behavior;

use Tuxxedo\Database\Query\FullText\FullText;
use Tuxxedo\Model\Attribute\Column\SearchText;
use Tuxxedo\Model\MetaData\ModelColumnInterface;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Reflection\PropertyReflector;

class SearchTextBehavior implements BeforeInsertBehaviorInterface, BeforeUpdateBehaviorInterface
{
    public function beforeInsert(
        object $model,
        ModelColumnInterface $column,
    ): void {
        $this->fill($model, $column);
    }

    public function beforeUpdate(
        object $model,
        ModelColumnInterface $column,
    ): void {
        $this->fill($model, $column);
    }

    private function fill(
        object $model,
        ModelColumnInterface $column,
    ): void {
        $attribute = $column->attribute;

        if (!$attribute instanceof SearchText) {
            return; // @codeCoverageIgnore
        }

        $parts = [];

        foreach ($attribute->sources as $source) {
            if (!\property_exists($model, $source)) {
                throw ModelException::fromSearchTextUnknownSource(
                    modelClass: $model::class,
                    property: $column->property,
                    source: $source,
                );
            }

            $value = PropertyReflector::createFromObject($model, $source)->getValue($model);

            if (\is_string($value) || $value instanceof \Stringable) {
                $parts[] = (string) $value;
            }
        }

        PropertyReflector::createFromObject($model, $column->property)
            ->setValue($model, FullText::normalize(\join(' ', $parts)));
    }
}
