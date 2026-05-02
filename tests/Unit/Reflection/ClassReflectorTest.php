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
use Tuxxedo\Reflection\ClassReflector;
use Unit\Fixture\Reflection\AnotherSimpleAttribute;
use Unit\Fixture\Reflection\ClassIntrospector;
use Unit\Fixture\Reflection\SimpleAttribute;

class ClassReflectorTest extends TestCase
{
    public function testClassNamePropertyHook(): void
    {
        $reflection = ClassReflector::createFromObject(new ClassIntrospector());

        self::assertSame(ClassIntrospector::class, $reflection->name);
    }

    public function testClassCreateFromObject(): void
    {
        $reflection = ClassReflector::createFromObject(new ClassIntrospector());

        self::assertInstanceOf(ClassReflector::class, $reflection);
    }

    public function testClassProperties(): void
    {
        $reflection = ClassReflector::createFromObject(new ClassIntrospector());

        $properties = \iterator_to_array($reflection->properties());

        self::assertCount(1, $properties);
        self::assertSame('property', $properties[0]->name);
    }

    public function testClassProperty(): void
    {
        $reflection = ClassReflector::createFromObject(new ClassIntrospector());

        self::assertSame('property', $reflection->property('property')->name);
    }

    public function testClassMethods(): void
    {
        $reflection = ClassReflector::createFromObject(new ClassIntrospector());

        $methods = \iterator_to_array($reflection->methods());

        self::assertCount(1, $methods);
        self::assertSame('method', $methods[0]->name);
    }

    public function testClassMethod(): void
    {
        $reflection = ClassReflector::createFromObject(new ClassIntrospector());

        self::assertSame('method', $reflection->method('method')->name);
    }

    public function testClassHasAttribute(): void
    {
        $reflection = ClassReflector::createFromObject(new ClassIntrospector());

        self::assertTrue($reflection->hasAttribute(SimpleAttribute::class));
        self::assertFalse($reflection->hasAttribute(AnotherSimpleAttribute::class));
    }

    public function testClassGetAttribute(): void
    {
        $reflection = ClassReflector::createFromObject(new ClassIntrospector());

        self::assertSame('one', $reflection->getAttribute(SimpleAttribute::class)->value);
    }

    public function testClassGetAttributeFailure(): void
    {
        $reflection = ClassReflector::createFromObject(new ClassIntrospector());

        self::expectException(\ReflectionException::class);

        $reflection->getAttribute(AnotherSimpleAttribute::class);
    }

    public function testClassGetAttributes(): void
    {
        $reflection = ClassReflector::createFromObject(new ClassIntrospector());

        /** @var array{0: SimpleAttribute, 1: SimpleAttribute} $attributes */
        $attributes = \iterator_to_array($reflection->getAttributes(SimpleAttribute::class));

        self::assertSame('one', $attributes[0]->value);
        self::assertSame('two', $attributes[1]->value);
    }
}
