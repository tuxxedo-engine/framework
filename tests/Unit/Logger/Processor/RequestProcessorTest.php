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

namespace Unit\Logger\Processor;

use Fixture\Http\Kernel\DispatcherController;
use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\LogRecord;
use Tuxxedo\Logger\LogRecordInterface;
use Tuxxedo\Logger\Processor\Info\HttpRequestInfoInterface;
use Tuxxedo\Logger\Processor\RequestProcessor;
use Tuxxedo\Router\DispatchableRoute;
use Tuxxedo\Router\Route;
use Tuxxedo\Temporal\Instant;

class RequestProcessorTest extends TestCase
{
    private function createRecord(): LogRecordInterface
    {
        return new LogRecord(
            message: 'message',
            level: LogLevel::INFO,
            timestamp: Instant::parse(input: '2026-01-01T12:00:00.000000+00:00'),
        );
    }

    private function createRequest(): Request
    {
        return new Request(
            serializerManager: SerializerManagerFactory::create(),
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
            method: Method::POST,
            path: '/orders/42/pay',
            host: 'localhost',
            ipAddress: '127.0.0.1',
        );
    }

    public function testAddsNothingWithoutBoundRequest(): void
    {
        $record = (new RequestProcessor())->process(new Container(), $this->createRecord());

        self::assertSame([], $record->extra);
    }

    public function testAddsRequestKeysWithoutRoute(): void
    {
        $container = new Container();
        $container->singleton($this->createRequest());

        $record = (new RequestProcessor())->process($container, $this->createRecord());

        self::assertSame(['http'], \array_keys($record->extra));

        $info = $record->extra['http'];

        self::assertInstanceOf(HttpRequestInfoInterface::class, $info);
        self::assertSame(Method::POST, $info->method);
        self::assertSame('/orders/42/pay', $info->path);
        self::assertSame('localhost', $info->host);
        self::assertSame('127.0.0.1', $info->clientIp);
        self::assertNull($info->route);
    }

    public function testAddsRouteWhenRouted(): void
    {
        $container = new Container();
        $container->singleton(
            $this->createRequest()->withRoute(
                new DispatchableRoute(
                    route: new Route(
                        method: null,
                        path: '/orders/{id}/pay',
                        controller: DispatcherController::class,
                        action: 'index',
                    ),
                ),
            ),
        );

        $record = (new RequestProcessor())->process($container, $this->createRecord());

        $info = $record->extra['http'];

        self::assertInstanceOf(HttpRequestInfoInterface::class, $info);
        self::assertSame('/orders/{id}/pay', $info->route);
        self::assertSame(Method::POST, $info->method);
    }

    public function testResolvesRequestWhenProcessing(): void
    {
        $container = new Container();
        $processor = new RequestProcessor();

        $before = $processor->process($container, $this->createRecord());

        self::assertSame([], $before->extra);

        $container->singleton($this->createRequest());

        $after = $processor->process($container, $this->createRecord());

        $info = $after->extra['http'];

        self::assertInstanceOf(HttpRequestInfoInterface::class, $info);
        self::assertSame('/orders/42/pay', $info->path);
    }
}
