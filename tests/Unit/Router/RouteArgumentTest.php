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

use PHPUnit\Framework\TestCase;
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
}
