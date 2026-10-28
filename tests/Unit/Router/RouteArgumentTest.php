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

namespace Unit\Router;

use Fixture\Router\RouteDiscoverer\Support\TestIntEnum;
use Fixture\Router\RouteDiscoverer\Support\TestStringEnum;
use Fixture\Router\RouteDiscoverer\Support\TestUnitEnum;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Router\ArgumentKind;
use Tuxxedo\Router\ArgumentNode;
use Tuxxedo\Router\RouteArgument;
use Tuxxedo\Router\RouteArgumentInterface;

class RouteArgumentTest extends TestCase
{
    private function makeNode(
        string $name = 'id',
        ArgumentKind $kind = ArgumentKind::TYPED_IMPLICIT,
        bool $optional = false,
        bool $prefixed = false,
    ): ArgumentNode {
        return new ArgumentNode(
            name: $name,
            kind: $kind,
            optional: $optional,
            prefixed: $prefixed,
        );
    }

    public function testConstructorExposesNode(): void
    {
        $node = $this->makeNode();

        $argument = new RouteArgument(
            node: $node,
            mappedName: null,
            nativeType: 'int',
            allowsNull: false,
            defaultValue: null,
        );

        self::assertSame($node, $argument->node);
    }

    public function testConstructorExposesMappedName(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(),
            mappedName: 'userId',
            nativeType: 'int',
            allowsNull: false,
            defaultValue: null,
        );

        self::assertSame('userId', $argument->mappedName);
    }

    public function testConstructorExposesMappedNameAsNull(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(),
            mappedName: null,
            nativeType: 'int',
            allowsNull: false,
            defaultValue: null,
        );

        self::assertNull($argument->mappedName);
    }

    public function testConstructorExposesNativeType(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(),
            mappedName: null,
            nativeType: 'string',
            allowsNull: false,
            defaultValue: null,
        );

        self::assertSame('string', $argument->nativeType);
    }

    public function testConstructorExposesAllowsNull(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(),
            mappedName: null,
            nativeType: 'int',
            allowsNull: true,
            defaultValue: null,
        );

        self::assertTrue($argument->allowsNull);
    }

    public function testConstructorExposesDefaultValue(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(),
            mappedName: null,
            nativeType: 'int',
            allowsNull: false,
            defaultValue: 42,
        );

        self::assertSame(42, $argument->defaultValue);
    }

    public function testConstructorDefaultsEnumClassToNull(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(),
            mappedName: null,
            nativeType: 'int',
            allowsNull: false,
            defaultValue: null,
        );

        self::assertNull($argument->enumClass);
    }

    public function testConstructorExposesEnumClass(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(),
            mappedName: null,
            nativeType: TestStringEnum::class,
            allowsNull: false,
            defaultValue: null,
            enumClass: TestStringEnum::class,
        );

        self::assertSame(TestStringEnum::class, $argument->enumClass);
    }

    public function testImplementsRouteArgumentInterface(): void
    {
        self::assertInstanceOf(
            RouteArgumentInterface::class,
            new RouteArgument(
                node: $this->makeNode(),
                mappedName: null,
                nativeType: 'int',
                allowsNull: false,
                defaultValue: null,
            ),
        );
    }

    public function testGetValueReturnsMatchByNodeName(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(),
            mappedName: null,
            nativeType: 'int',
            allowsNull: false,
            defaultValue: null,
        );

        self::assertSame(
            5,
            $argument->getValue(
                [
                    'id' => '5',
                ],
            ),
        );
    }

    public function testGetValueReturnsMatchByMappedName(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(
                name: 'userId',
            ),
            mappedName: 'id',
            nativeType: 'int',
            allowsNull: false,
            defaultValue: null,
        );

        self::assertSame(
            7,
            $argument->getValue(
                [
                    'id' => '7',
                ],
            ),
        );
    }

    public function testGetValuePrefersNodeNameOverMappedName(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(
                name: 'userId',
            ),
            mappedName: 'id',
            nativeType: 'int',
            allowsNull: false,
            defaultValue: null,
        );

        self::assertSame(
            3,
            $argument->getValue(
                [
                    'userId' => '3',
                    'id' => '9',
                ],
            ),
        );
    }

    public function testGetValueCastsToNativeType(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(),
            mappedName: null,
            nativeType: 'int',
            allowsNull: false,
            defaultValue: null,
        );

        $value = $argument->getValue(
            [
                'id' => '42',
            ],
        );

        self::assertSame(42, $value);
    }

    public function testGetValueCastsStringType(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(
                name: 'slug',
            ),
            mappedName: null,
            nativeType: 'string',
            allowsNull: false,
            defaultValue: null,
        );

        self::assertSame(
            'hello',
            $argument->getValue(
                [
                    'slug' => 'hello',
                ],
            ),
        );
    }

    public function testGetValueReturnsDefaultWhenOptionalAndMissing(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(
                name: 'page',
                optional: true,
            ),
            mappedName: null,
            nativeType: 'int',
            allowsNull: false,
            defaultValue: 1,
        );

        self::assertSame(
            1,
            $argument->getValue(
                [],
            ),
        );
    }

    public function testGetValueReturnsDefaultWhenOptionalAndEmpty(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(
                name: 'page',
                optional: true,
            ),
            mappedName: null,
            nativeType: 'int',
            allowsNull: false,
            defaultValue: 1,
        );

        self::assertSame(
            1,
            $argument->getValue(
                [
                    'page' => '',
                ],
            ),
        );
    }

    public function testGetValueReturnsNullWhenAllowsNullAndNoMatch(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(),
            mappedName: null,
            nativeType: 'int',
            allowsNull: true,
            defaultValue: null,
        );

        self::assertNull(
            $argument->getValue(
                [],
            ),
        );
    }

    public function testGetValueDoesNotReturnDefaultForNonOptionalMissingMatch(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(),
            mappedName: null,
            nativeType: 'int',
            allowsNull: true,
            defaultValue: 99,
        );

        self::assertNull(
            $argument->getValue(
                [],
            ),
        );
    }

    public function testGetValueHydratesStringBackedEnum(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(
                name: 'feed',
            ),
            mappedName: null,
            nativeType: TestStringEnum::class,
            allowsNull: false,
            defaultValue: null,
            enumClass: TestStringEnum::class,
        );

        self::assertSame(
            TestStringEnum::BEST_SELLERS,
            $argument->getValue(
                [
                    'feed' => 'best.sellers',
                ],
            ),
        );
    }

    public function testGetValueHydratesIntBackedEnum(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(
                name: 'level',
            ),
            mappedName: null,
            nativeType: TestIntEnum::class,
            allowsNull: false,
            defaultValue: null,
            enumClass: TestIntEnum::class,
        );

        self::assertSame(
            TestIntEnum::MINUS_ONE,
            $argument->getValue(
                [
                    'level' => '-1',
                ],
            ),
        );
    }

    public function testGetValueHydratesUnitEnumByCaseName(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(
                name: 'unit',
            ),
            mappedName: null,
            nativeType: TestUnitEnum::class,
            allowsNull: false,
            defaultValue: null,
            enumClass: TestUnitEnum::class,
        );

        self::assertSame(
            TestUnitEnum::BETA,
            $argument->getValue(
                [
                    'unit' => 'BETA',
                ],
            ),
        );
    }

    public function testGetValueReturnsEnumDefaultWhenOptionalAndMissing(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(
                name: 'feed',
                optional: true,
            ),
            mappedName: null,
            nativeType: TestStringEnum::class,
            allowsNull: false,
            defaultValue: TestStringEnum::NEWEST,
            enumClass: TestStringEnum::class,
        );

        self::assertSame(
            TestStringEnum::NEWEST,
            $argument->getValue(
                [],
            ),
        );
    }

    public function testGetValueReturnsNullForNullableEnumWhenOptionalAndMissing(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(
                name: 'unit',
                optional: true,
            ),
            mappedName: null,
            nativeType: TestUnitEnum::class,
            allowsNull: true,
            defaultValue: null,
            enumClass: TestUnitEnum::class,
        );

        self::assertNull(
            $argument->getValue(
                [],
            ),
        );
    }

    public function testGetValueThrowsNotFoundForUnknownEnumValue(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(
                name: 'feed',
            ),
            mappedName: null,
            nativeType: TestStringEnum::class,
            allowsNull: false,
            defaultValue: null,
            enumClass: TestStringEnum::class,
        );

        try {
            $argument->getValue(
                [
                    'feed' => 'NEWEST',
                ],
            );

            self::fail('Expected HttpException was not thrown');
        } catch (HttpException $exception) {
            self::assertSame(ResponseCode::NOT_FOUND, $exception->responseCode);
        }
    }

    public function testGetValueThrowsNotFoundForMissingEnumValue(): void
    {
        $argument = new RouteArgument(
            node: $this->makeNode(
                name: 'feed',
            ),
            mappedName: null,
            nativeType: TestStringEnum::class,
            allowsNull: false,
            defaultValue: null,
            enumClass: TestStringEnum::class,
        );

        try {
            $argument->getValue(
                [],
            );

            self::fail('Expected HttpException was not thrown');
        } catch (HttpException $exception) {
            self::assertSame(ResponseCode::NOT_FOUND, $exception->responseCode);
        }
    }
}
