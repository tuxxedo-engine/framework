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

namespace Unit\Http\Request\Attribute;

use Fixture\Http\Request\Attribute\AttributeMappingFixture;
use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\RecordingBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Reflection\StubParameterReflector;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Attribute\JsonBodyMapToArrayOf;
use Tuxxedo\Http\Request\Request;

class JsonBodyMapToArrayOfTest extends TestCase
{
    private function makeContainer(
        RecordingBodyContext $body,
    ): Container {
        $container = new Container();

        $container->persistent(
            class: new Request(
                headers: new StubHeaderContext(),
                cookies: new StubInputContext(),
                get: new StubInputContext(),
                post: new StubInputContext(),
                files: new StubUploadedFilesContext(),
                body: $body,
            ),
        );

        return $container;
    }

    public function testResolveDelegatesToBodyJsonMapToArrayOf(): void
    {
        $items = [
            new AttributeMappingFixture(
                name: 'John',
            ),
            new AttributeMappingFixture(
                name: 'Jane',
            ),
        ];

        $body = new RecordingBodyContext(
            jsonMapToArrayOfReturn: $items,
        );

        $result = (new JsonBodyMapToArrayOf(AttributeMappingFixture::class))->resolve(
            container: $this->makeContainer(
                body: $body,
            ),
            parameter: new StubParameterReflector(),
        );

        self::assertSame($items, $result);
        self::assertNotNull($body->jsonMapToArrayOfCall);
        self::assertSame(AttributeMappingFixture::class, $body->jsonMapToArrayOfCall['className']);
        self::assertSame(0, $body->jsonMapToArrayOfCall['flags']);
    }

    public function testResolvePassesFlagsToBodyJsonMapToArrayOf(): void
    {
        $body = new RecordingBodyContext();

        (new JsonBodyMapToArrayOf(
            className: AttributeMappingFixture::class,
            flags: \JSON_BIGINT_AS_STRING,
        ))->resolve(
            container: $this->makeContainer(
                body: $body,
            ),
            parameter: new StubParameterReflector(),
        );

        self::assertNotNull($body->jsonMapToArrayOfCall);
        self::assertSame(\JSON_BIGINT_AS_STRING, $body->jsonMapToArrayOfCall['flags']);
    }

    public function testResolveWrapsBodyExceptionsAsBadRequest(): void
    {
        $body = new RecordingBodyContext();
        $body->throwOnMap = true;

        $this->expectException(HttpException::class);

        (new JsonBodyMapToArrayOf(AttributeMappingFixture::class))->resolve(
            container: $this->makeContainer(
                body: $body,
            ),
            parameter: new StubParameterReflector(),
        );
    }
}
