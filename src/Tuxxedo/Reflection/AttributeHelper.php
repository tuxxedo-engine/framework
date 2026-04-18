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

namespace Tuxxedo\Reflection;

class AttributeHelper implements AttributeHelperInterface
{
    public function hasAttribute(
        \ReflectionParameter|\ReflectionProperty $reflector,
        string $attribute,
    ): bool {
        return \sizeof($reflector->getAttributes($attribute, \ReflectionAttribute::IS_INSTANCEOF)) > 0;
    }

    /**
     * @template TAttributeName of object
     *
     * @param class-string<TAttributeName> $attribute
     * @return TAttributeName
     */
    public function getAttribute(
        \ReflectionParameter|\ReflectionProperty $reflector,
        string $attribute,
    ): object {
        $attributes = $reflector->getAttributes(
            name: $attribute,
            flags: \ReflectionAttribute::IS_INSTANCEOF,
        );

        if (\sizeof($attributes) === 0) {
            throw new \ReflectionException();
        }

        /** @var TAttributeName */
        return $attributes[0]->newInstance();
    }

    /**
     * @template TAttributeName of object
     *
     * @param class-string<TAttributeName> $attribute
     * @return \Generator<TAttributeName>
     */
    public function getAttributes(
        \ReflectionParameter|\ReflectionProperty $reflector,
        string $attribute,
    ): \Generator {
        $attributes = $reflector->getAttributes(
            name: $attribute,
            flags: \ReflectionAttribute::IS_INSTANCEOF,
        );

        /** @var \ReflectionAttribute<TAttributeName> $attr */
        foreach ($attributes as $attr) {
            yield $attr->newInstance();
        }
    }
}
