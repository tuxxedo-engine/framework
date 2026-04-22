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

namespace Tuxxedo\Model\MetaData\Adapter;

use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\MetaData\ModelMetaData;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Reflection\ClassReflector;

class ReflectionMetaDataAdapter implements MetaDataAdapterInterface
{
    /**
     * @param class-string $model
     *
     * @throws ModelException
     */
    public function getModel(
        string $model,
    ): ModelMetaDataInterface {
        try {
            $class = new ClassReflector(new \ReflectionClass($model));

            if (
                $class->reflector->isAbstract() ||
                $class->reflector->isTrait() ||
                $class->reflector->isInterface() ||
                $class->reflector->isEnum()
            ) {
                throw new \ReflectionException();
            }
        } catch (\ReflectionException) {
            throw ModelException::fromInvalidModelClass(
                modelClass: $model,
            );
        }

        return new ModelMetaData(
            model: $model,
            table: $this->getTable($class),
        );
    }

    /**
     * @throws ModelException
     */
    private function getTable(
        ClassReflector $class,
    ): string {
        try {
            return $class->getAttribute(Table::class)->name;
        } catch (\ReflectionException) {
            throw ModelException::fromMissingTableAttribute(
                modelClass: $class->reflector->getName(),
            );
        }
    }
}
