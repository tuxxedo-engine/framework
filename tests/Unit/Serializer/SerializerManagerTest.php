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

namespace Unit\Serializer;

use Fixture\Serializer\Address;
use Fixture\Serializer\Validated;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Serializer\JsonSerializer;
use Tuxxedo\Serializer\MetaData\ClassMetaData;
use Tuxxedo\Serializer\MetaData\MetaDataReader;
use Tuxxedo\Serializer\SerializerManager;
use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\Validator;

class SerializerManagerTest extends TestCase
{
    public function testSerializersValidateByDefault(): void
    {
        $manager = new SerializerManager(new Validator(new Container()));

        try {
            $manager->json()->decode('{"quantity":-1}', Validated::class);

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertSame('quantity', $exception->violations[0]->propertyPath);
        }

        self::assertSame(
            -1,
            $manager->array(skipValidation: true)->fromArray(
                [
                    'quantity' => '-1',
                ],
                Validated::class,
            )->quantity,
        );
    }

    public function testOptionsArePassedThrough(): void
    {
        $manager = new SerializerManager(new Validator(new Container()));

        try {
            $manager->array(skipUnknownKeys: false)->fromArray(
                [
                    'street' => 'A',
                    'city' => 'B',
                    'zip' => 'C',
                ],
                Address::class,
            );

            self::fail('Expected ValidationException was not thrown');
        } catch (ValidationException $exception) {
            self::assertSame('zip', $exception->violations[0]->propertyPath);
        }

        $json = $manager->json(
            flags: \JSON_PRETTY_PRINT,
            depth: 8,
        );

        self::assertInstanceOf(JsonSerializer::class, $json);
        self::assertSame(\JSON_PRETTY_PRINT, $json->flags);
        self::assertSame(8, $json->depth);
    }

    public function testMetaDataIsSharedAcrossSerializers(): void
    {
        $reader = new class () extends MetaDataReader {
            public int $reads = 0;

            public function read(
                string $className,
            ): ClassMetaData {
                $this->reads++;

                return parent::read($className);
            }
        };

        $manager = new SerializerManager(
            validator: new Validator(new Container()),
            metaData: $reader,
        );

        $manager->array()->fromArray(
            [
                'street' => 'A',
                'city' => 'B',
            ],
            Address::class,
        );

        $manager->json()->encode(new Address('A', 'B'));

        self::assertSame(2, $reader->reads);
    }
}
