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

namespace Unit\Http\Kernel\Resolver;

use Fixture\Http\Request\Context\BodyContextFixture;
use Fixture\Serializer\Validated;
use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Reflection\StubParameterReflector;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Kernel\Resolver\Query;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Serializer\SerializerInputException;
use Tuxxedo\Validator\ValidationException;

class QueryTest extends TestCase
{
    protected function setUp(): void
    {
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
    }

    /**
     * @param class-string|null $defaultType
     */
    private function resolve(
        Query $query,
        ?string $defaultType = BodyContextFixture::class,
        ?string $builtinType = null,
    ): mixed {
        $container = new Container();

        $container->singleton(
            class: new Request(
                serializerManager: SerializerManagerFactory::create(),
                headers: new StubHeaderContext(),
                files: new StubUploadedFilesContext(),
                body: new StubBodyContext(),
                method: Method::GET,
            ),
        );

        return $query->resolve(
            container: $container,
            parameter: new StubParameterReflector(
                defaultType: $defaultType,
                builtinType: $builtinType,
            ),
        );
    }

    public function testQueryStringIsMappedLeniently(): void
    {
        $_GET = [
            'name' => 'John',
            'age' => '30',
        ];

        $result = $this->resolve(new Query());

        self::assertInstanceOf(BodyContextFixture::class, $result);
        self::assertSame('John', $result->name);
        self::assertSame(30, $result->age);
    }

    public function testKeyAndOnly(): void
    {
        $_GET['filter'] = [
            'name' => 'John',
            'age' => '30',
        ];

        $result = $this->resolve(
            new Query(
                key: 'filter',
                only: [
                    'age',
                ],
            ),
        );

        self::assertInstanceOf(BodyContextFixture::class, $result);
        self::assertSame('', $result->name);
        self::assertSame(30, $result->age);
    }

    public function testListForArrayParameters(): void
    {
        $_GET['people'] = [
            [
                'name' => 'John',
            ],
            [
                'name' => 'Jane',
            ],
        ];

        $result = $this->resolve(
            query: new Query(
                className: BodyContextFixture::class,
                key: 'people',
            ),
            defaultType: null,
            builtinType: 'array',
        );

        self::assertIsArray($result);
        self::assertCount(2, $result);
    }

    public function testNonArrayKeyIs400(): void
    {
        $_GET['filter'] = 'John';

        try {
            $this->resolve(
                new Query(
                    key: 'filter',
                ),
            );

            self::fail('Expected HttpException was not thrown');
        } catch (HttpException $exception) {
            self::assertSame(ResponseCode::BAD_REQUEST, $exception->responseCode);
            self::assertInstanceOf(SerializerInputException::class, $exception->getPrevious());
        }
    }

    public function testInvalidValuesBubbleUpAsValidationException(): void
    {
        $_GET = [
            'quantity' => '-1',
        ];

        $this->expectException(ValidationException::class);

        $this->resolve(
            query: new Query(),
            defaultType: Validated::class,
        );
    }

    public function testMissingClassIs500(): void
    {
        try {
            $this->resolve(
                query: new Query(),
                defaultType: null,
                builtinType: 'array',
            );

            self::fail('Expected HttpException was not thrown');
        } catch (HttpException $exception) {
            self::assertSame(ResponseCode::INTERNAL_SERVER_ERROR, $exception->responseCode);
        }
    }
}
