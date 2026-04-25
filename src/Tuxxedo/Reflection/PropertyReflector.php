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

class PropertyReflector implements PropertyReflectorInterface
{
    public string $name {
        get {
            return $this->reflector->name;
        }
    }

    final public function __construct(
        public readonly \ReflectionProperty $reflector,
        private readonly TypeHelperInterface $typeHelper = new TypeHelper(),
        private readonly AttributeHelperInterface $attributeHelper = new AttributeHelper(),
    ) {
    }

    /**
     * @param object|class-string $object
     *
     * @throws \ReflectionException
     */
    public static function createFromObject(
        object|string $object,
        string $property,
    ): self {
        return new static(
            reflector: new \ReflectionProperty($object, $property),
        );
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

    public function getValue(
        ?object $object = null,
    ): mixed {
        if (!$this->reflector->isInitialized($object)) {
            return null;
        }

        return $this->reflector->getValue($object);
    }

    public function setValue(
        object $object,
        mixed $value,
    ): void {
        $this->reflector->setValue($object, $value);
    }
}
