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

namespace Tuxxedo\Model\Aggregate;

use Tuxxedo\Model\MetaData\ModelRelationInterface;
use Tuxxedo\Model\MetaData\MorphToRelationMetaDataInterface;
use Tuxxedo\Model\RelationInterface;
use Tuxxedo\Reflection\PropertyReflector;

class RelationLoadState
{
    public static function isLoaded(
        object $model,
        ModelRelationInterface|MorphToRelationMetaDataInterface $relation,
    ): bool {
        $reflectionProperty = PropertyReflector::createFromObject($model, $relation->property);

        if (!$reflectionProperty->reflector->isInitialized($model)) {
            return false; // @codeCoverageIgnore
        }

        $value = $reflectionProperty->getValue($model);

        if ($value === null) {
            return false;
        }

        if ($value instanceof RelationInterface) {
            return $value->isMaterialized() ||
                $value->pendingAdds !== [] ||
                $value->pendingRemoves !== [];
        }

        if (!\is_object($value)) {
            return false; // @codeCoverageIgnore
        }

        return !(new \ReflectionClass($value))->isUninitializedLazyObject($value);
    }
}
