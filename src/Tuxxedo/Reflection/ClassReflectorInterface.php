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

interface ClassReflectorInterface extends AttributeInterface
{
    /**
     * @var \ReflectionClass<object>
     */
    public \ReflectionClass $reflector {
        get;
    }

    /**
     * @var class-string
     */
    public string $name {
        get;
    }

    /**
     * @param int-mask-of<\ReflectionProperty::IS_*>|null $filter
     * @return \Generator<PropertyReflectorInterface>
     */
    public function properties(
        ?int $filter = null,
    ): \Generator;

    /**
     * @throws \ReflectionException
     */
    public function property(
        string $name,
    ): PropertyReflectorInterface;

    /**
     * @param int-mask-of<\ReflectionMethod::IS_*>|null $filter
     * @return \Generator<MethodReflectorInterface>
     */
    public function methods(
        ?int $filter = null,
    ): \Generator;

    /**
     * @throws \ReflectionException
     */
    public function method(
        string $name,
    ): MethodReflectorInterface;
}
