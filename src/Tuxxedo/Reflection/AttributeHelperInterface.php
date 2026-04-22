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

interface AttributeHelperInterface
{
    /**
     * @param \ReflectionClass<object>|\ReflectionParameter|\ReflectionProperty|\ReflectionMethod $reflector
     * @param class-string $attribute
     */
    public function hasAttribute(
        \ReflectionClass|\ReflectionParameter|\ReflectionProperty|\ReflectionMethod $reflector,
        string $attribute,
    ): bool;

    /**
     * @template TAttributeName of object
     *
     * @param \ReflectionClass<object>|\ReflectionParameter|\ReflectionProperty|\ReflectionMethod $reflector
     * @param class-string<TAttributeName> $attribute
     * @return TAttributeName
     */
    public function getAttribute(
        \ReflectionClass|\ReflectionParameter|\ReflectionProperty|\ReflectionMethod $reflector,
        string $attribute,
    ): object;

    /**
     * @template TAttributeName of object
     *
     * @param \ReflectionClass<object>|\ReflectionParameter|\ReflectionProperty|\ReflectionMethod $reflector
     * @param class-string<TAttributeName> $attribute
     * @return \Generator<TAttributeName>
     */
    public function getAttributes(
        \ReflectionClass|\ReflectionParameter|\ReflectionProperty|\ReflectionMethod $reflector,
        string $attribute,
    ): \Generator;
}
