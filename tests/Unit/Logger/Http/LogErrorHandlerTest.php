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

namespace Unit\Logger\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Container\Container;
use Tuxxedo\Http\Request\Request;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Logger\Http\LogErrorHandler;
use Tuxxedo\Logger\LogLevel;
use Tuxxedo\Logger\MemoryLogger;

class LogErrorHandlerTest extends TestCase
{
    private function makeRequest(): RequestInterface
    {
        return new Request(
            serializerManager: SerializerManagerFactory::create(),
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
        );
    }

    /**
     * @return \Generator<array{0: ResponseCode, 1: LogLevel}>
     */
    public static function levelDataProvider(): \Generator
    {
        yield [
            ResponseCode::INTERNAL_SERVER_ERROR,
            LogLevel::CRITICAL,
        ];

        yield [
            ResponseCode::SERVICE_UNAVAILABLE,
            LogLevel::CRITICAL,
        ];

        yield [
            ResponseCode::NOT_FOUND,
            LogLevel::ERROR,
        ];

        yield [
            ResponseCode::OK,
            LogLevel::ERROR,
        ];
    }

    #[DataProvider('levelDataProvider')]
    public function testLogsExceptionAndReturnsResponse(
        ResponseCode $responseCode,
        LogLevel $level,
    ): void {
        $logger = new MemoryLogger(
            container: new Container(),
        );
        $exception = new \RuntimeException('failure');
        $response = new Response(
            responseCode: $responseCode,
        );

        $result = (new LogErrorHandler($logger))->handle(
            request: $this->makeRequest(),
            response: $response,
            exception: $exception,
        );

        self::assertSame($response, $result);
        self::assertCount(1, $logger);

        $record = $logger->records[0];

        self::assertSame('Unhandled {class}', $record->message);
        self::assertSame(['class' => \RuntimeException::class], $record->context);
        self::assertSame($level, $record->level);
        self::assertSame($exception, $record->exception);
    }
}
