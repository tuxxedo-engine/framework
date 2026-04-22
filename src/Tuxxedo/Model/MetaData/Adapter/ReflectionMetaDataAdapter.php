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

use Tuxxedo\Model\MetaData\ModelMetaData;
use Tuxxedo\Model\MetaData\ModelMetaDataInterface;
use Tuxxedo\Model\ModelException;

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
            // @todo Class reflection in Reflection namespace?
            $reflection = new \ReflectionClass($model);

            if (
                $reflection->isAbstract() ||
                $reflection->isTrait() ||
                $reflection->isInterface() ||
                $reflection->isEnum()
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
            table: $this->getTable($reflection),
        );
    }

    /**
     * @param \ReflectionClass<object> $reflection
     *
     * @todo Add @throw-tag
     */
    private function getTable(
        \ReflectionClass $reflection,
    ): string {
        return '';
    }
}
