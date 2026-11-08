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

namespace Unit\Serializer\MetaData;

use Fixture\Serializer\ClassicConstructor;
use Fixture\Serializer\Customer;
use Fixture\Serializer\MoneyConverter;
use Fixture\Serializer\Order;
use Fixture\Serializer\Plain;
use Fixture\Serializer\Schedule;
use Fixture\Serializer\YesNo;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Serializer\Attribute\DateFormat;
use Tuxxedo\Serializer\MetaData\MetaDataReader;
use Tuxxedo\Serializer\MetaData\PropertyMetaData;

class MetaDataReaderTest extends TestCase
{
    public function testReadIsCached(): void
    {
        $reader = new MetaDataReader();

        self::assertSame($reader->read(Customer::class), $reader->read(Customer::class));
    }

    public function testKeysFollowCaseStyleAndNameOverrides(): void
    {
        $properties = self::byName(new MetaDataReader()->read(Customer::class)->properties);

        self::assertSame('first_name', $properties['firstName']->key);
        self::assertSame('e-mail', $properties['email']->key);
        self::assertSame('tag_ids', $properties['tagIds']->key);
        self::assertTrue($properties['secret']->ignore);
        self::assertSame('int', $properties['tagIds']->listOf);
        self::assertSame('float', $properties['prices']->mapOf);
        self::assertTrue($properties['id']->constructorParameter);
        self::assertFalse($properties['id']->hasDefault);
        self::assertTrue($properties['active']->hasDefault);
        self::assertFalse($properties['secret']->constructorParameter);
    }

    public function testTypes(): void
    {
        $properties = self::byName(new MetaDataReader()->read(Plain::class)->properties);

        self::assertArrayNotHasKey('instances', $properties);
        self::assertSame('int', $properties['count']->typeName);
        self::assertTrue($properties['count']->builtin);
        self::assertFalse($properties['count']->nullable);
        self::assertSame(Plain::class, $properties['child']->typeName);
        self::assertFalse($properties['child']->builtin);
        self::assertNull($properties['anything']->typeName);
        self::assertNull($properties['identifier']->typeName);
        self::assertFalse($properties['identifier']->nullable);
        self::assertNull($properties['untyped']->typeName);
        self::assertTrue($properties['untyped']->nullable);
    }

    public function testNonPromotedConstructorParameters(): void
    {
        $properties = self::byName(new MetaDataReader()->read(ClassicConstructor::class)->properties);

        self::assertTrue($properties['name']->readable);
        self::assertTrue($properties['name']->constructorParameter);
        self::assertFalse($properties['suffix']->readable);
        self::assertTrue($properties['suffix']->hasDefault);
        self::assertTrue($properties['suffixed']->readable);
        self::assertFalse($properties['suffixed']->constructorParameter);
    }

    public function testConverters(): void
    {
        $reader = new MetaDataReader();
        $order = self::byName($reader->read(Order::class)->properties);
        $schedule = self::byName($reader->read(Schedule::class)->properties);

        self::assertInstanceOf(MoneyConverter::class, $order['total']->converter);
        self::assertInstanceOf(YesNo::class, $order['paid']->converter);
        self::assertInstanceOf(DateFormat::class, $schedule['closesAt']->converter);
        self::assertNull($schedule['at']->converter);
    }

    /**
     * @param list<PropertyMetaData> $properties
     * @return array<string, PropertyMetaData>
     */
    private static function byName(
        array $properties,
    ): array {
        $indexed = [];

        foreach ($properties as $property) {
            $indexed[$property->name] = $property;
        }

        return $indexed;
    }
}
