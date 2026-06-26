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
use Tuxxedo\Http\Request\Attribute\JsonBodyMapTo;
use Tuxxedo\Http\Request\Request;

class JsonBodyMapToTest extends TestCase
{
    private function makeContainer(
        RecordingBodyContext $body,
    ): Container {
        $container = new Container();

        $container->singleton(
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

    public function testResolveDelegatesToBodyJsonMapTo(): void
    {
        $fixture = new AttributeMappingFixture(
            name: 'John',
        );

        $body = new RecordingBodyContext(
            jsonMapToReturn: $fixture,
        );

        $result = (new JsonBodyMapTo(AttributeMappingFixture::class))->resolve(
            container: $this->makeContainer(
                body: $body,
            ),
            parameter: new StubParameterReflector(),
        );

        self::assertSame($fixture, $result);
        self::assertNotNull($body->jsonMapToCall);
        self::assertSame(AttributeMappingFixture::class, $body->jsonMapToCall['className']);
        self::assertSame(0, $body->jsonMapToCall['flags']);
    }

    public function testResolvePassesFlagsToBodyJsonMapTo(): void
    {
        $fixture = new AttributeMappingFixture();

        $body = new RecordingBodyContext(
            jsonMapToReturn: $fixture,
        );

        (new JsonBodyMapTo(
            className: AttributeMappingFixture::class,
            flags: \JSON_BIGINT_AS_STRING,
        ))->resolve(
            container: $this->makeContainer(
                body: $body,
            ),
            parameter: new StubParameterReflector(),
        );

        self::assertNotNull($body->jsonMapToCall);
        self::assertSame(\JSON_BIGINT_AS_STRING, $body->jsonMapToCall['flags']);
    }

    public function testResolveFallsBackToParameterDefaultTypeWhenClassNameIsNull(): void
    {
        $fixture = new AttributeMappingFixture();

        $body = new RecordingBodyContext(
            jsonMapToReturn: $fixture,
        );

        (new JsonBodyMapTo())->resolve(
            container: $this->makeContainer(
                body: $body,
            ),
            parameter: new StubParameterReflector(
                defaultType: AttributeMappingFixture::class,
            ),
        );

        self::assertNotNull($body->jsonMapToCall);
        self::assertSame(AttributeMappingFixture::class, $body->jsonMapToCall['className']);
    }

    public function testResolveThrowsBadRequestWhenNeitherClassNameNorDefaultTypeAvailable(): void
    {
        $body = new RecordingBodyContext();

        $this->expectException(HttpException::class);

        (new JsonBodyMapTo())->resolve(
            container: $this->makeContainer(
                body: $body,
            ),
            parameter: new StubParameterReflector(),
        );
    }

    public function testResolveWrapsBodyExceptionsAsBadRequest(): void
    {
        $body = new RecordingBodyContext();
        $body->throwOnMap = true;

        $this->expectException(HttpException::class);

        (new JsonBodyMapTo(
            className: AttributeMappingFixture::class,
        ))->resolve(
            container: $this->makeContainer(
                body: $body,
            ),
            parameter: new StubParameterReflector(),
        );
    }
}
