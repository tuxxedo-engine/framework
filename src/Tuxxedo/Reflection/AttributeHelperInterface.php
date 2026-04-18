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
     * @param class-string $attribute
     */
    public function hasAttribute(
        \ReflectionParameter|\ReflectionProperty $reflector,
        string $attribute,
    ): bool;

    /**
     * @template TAttributeName of object
     *
     * @param class-string<TAttributeName> $attribute
     * @return TAttributeName
     */
    public function getAttribute(
        \ReflectionParameter|\ReflectionProperty $reflector,
        string $attribute,
    ): object;

    /**
     * @template TAttributeName of object
     *
     * @param class-string<TAttributeName> $attribute
     * @return \Generator<TAttributeName>
     */
    public function getAttributes(
        \ReflectionParameter|\ReflectionProperty $reflector,
        string $attribute,
    ): \Generator;
}
