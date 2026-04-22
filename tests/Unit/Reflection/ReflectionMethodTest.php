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

namespace Unit\Reflection;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Reflection\MethodReflector;
use Unit\Fixture\Reflection\AnotherSimpleAttribute;
use Unit\Fixture\Reflection\MethodIntrospector;
use Unit\Fixture\Reflection\SimpleAttribute;

class ReflectionMethodTest extends TestCase
{
    public function testParameterHasAttribute(): void
    {
        $reflection = new MethodReflector(
            reflector: (new \ReflectionClass(MethodIntrospector::class)->getMethod('one')),
        );

        self::assertTrue($reflection->hasAttribute(SimpleAttribute::class));
        self::assertFalse($reflection->hasAttribute(AnotherSimpleAttribute::class));
    }

    public function testParameterGetAttributeAlwaysFirst(): void
    {
        $reflection = new MethodReflector(
            reflector: (new \ReflectionClass(MethodIntrospector::class)->getMethod('two')),
        );

        self::assertSame($reflection->getAttribute(SimpleAttribute::class)->value, 'one');
    }

    public function testMethodGetAttributeFailure(): void
    {
        $reflection = new MethodReflector(
            reflector: (new \ReflectionClass(MethodIntrospector::class)->getMethod('one')),
        );

        self::expectException(\ReflectionException::class);

        $reflection->getAttribute(AnotherSimpleAttribute::class);
    }

    public function testMethodGetAttributes(): void
    {
        $reflection = new MethodReflector(
            reflector: (new \ReflectionClass(MethodIntrospector::class)->getMethod('two')),
        );

        /** @var array{0: SimpleAttribute, 1: SimpleAttribute} $attributes */
        $attributes = \iterator_to_array($reflection->getAttributes(SimpleAttribute::class));

        self::assertSame($attributes[0]->value, 'one');
        self::assertSame($attributes[1]->value, 'two');
    }
}
