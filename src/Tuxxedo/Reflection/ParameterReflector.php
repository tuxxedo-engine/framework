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

class ParameterReflector implements ParameterReflectorInterface
{
    public string $name {
        get {
            return $this->reflector->name;
        }
    }

    final public function __construct(
        public readonly \ReflectionParameter $reflector,
        private readonly TypeHelperInterface $typeHelper = new TypeHelper(),
        private readonly AttributeHelperInterface $attributeHelper = new AttributeHelper(),
    ) {
    }

    public function getDefaultType(): ?string
    {
        return $this->typeHelper->getDefaultType($this->reflector);
    }

    public function getBuiltinType(): ?string
    {
        return $this->typeHelper->getBuiltinType($this->reflector);
    }

    public function isNullable(): bool
    {
        return $this->typeHelper->isNullable($this->reflector);
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
