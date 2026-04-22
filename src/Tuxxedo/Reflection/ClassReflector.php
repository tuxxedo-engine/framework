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

readonly class ClassReflector implements ClassReflectorInterface
{
    /**
     * @param \ReflectionClass<object> $reflector
     */
    public function __construct(
        public \ReflectionClass $reflector,
        private AttributeHelperInterface $attributeHelper = new AttributeHelper(),
    ) {
    }

    public function property(
        string $name,
    ): PropertyReflectorInterface {
        return new PropertyReflector(
            reflector: $this->reflector->getProperty($name),
            attributeHelper: $this->attributeHelper,
        );
    }

    public function method(
        string $name,
    ): MethodReflectorInterface {
        return new MethodReflector(
            reflector: $this->reflector->getMethod($name),
            attributeHelper: $this->attributeHelper,
        );
    }

    public function hasAttribute(
        string $attribute,
    ): bool {
        return $this->attributeHelper->hasAttribute($this->reflector, $attribute);
    }

    /**
     * @template TAttributeName of object
     *
     * @param class-string<TAttributeName> $attribute
     * @return TAttributeName
     */
    public function getAttribute(
        string $attribute,
    ): object {
        return $this->attributeHelper->getAttribute($this->reflector, $attribute);
    }

    /**
     * @template TAttributeName of object
     *
     * @param class-string<TAttributeName> $attribute
     * @return \Generator<TAttributeName>
     */
    public function getAttributes(
        string $attribute,
    ): \Generator {
        yield from $this->attributeHelper->getAttributes($this->reflector, $attribute);
    }
}
