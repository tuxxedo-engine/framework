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

namespace Integration\Http\Request;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Support\Http\PhpInputStreamWrapper;
use Support\Http\Request\Context\StubBodyContext;
use Support\Http\Request\Context\StubHeaderContext;
use Support\Http\Request\Context\StubInputContext;
use Support\Http\Request\Context\StubUploadedFilesContext;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Request;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class RequestQueryBodyHydrationTest extends TestCase
{
    protected function setUp(): void
    {
        $_POST = [];

        \stream_wrapper_unregister('php');
        \stream_wrapper_register('php', PhpInputStreamWrapper::class);
    }

    protected function tearDown(): void
    {
        \stream_wrapper_restore('php');

        $_POST = [];
        PhpInputStreamWrapper::$content = '';
    }

    public function testPopulatesPostFromFormEncodedBodyOnQueryRequest(): void
    {
        PhpInputStreamWrapper::$content = 'foo=bar&count=3';
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';

        new Request(
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
            method: Method::QUERY,
        );

        self::assertSame(
            [
                'foo' => 'bar',
                'count' => '3',
            ],
            $_POST,
        );
    }

    public function testPopulatesPostFromNestedFormEncodedBody(): void
    {
        PhpInputStreamWrapper::$content = 'filter[status]=open&filter[tag]=urgent';
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';

        new Request(
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
            method: Method::QUERY,
        );

        self::assertSame(
            [
                'filter' => [
                    'status' => 'open',
                    'tag' => 'urgent',
                ],
            ],
            $_POST,
        );
    }

    public function testAcceptsFormContentTypeWithCharsetParameter(): void
    {
        PhpInputStreamWrapper::$content = 'q=hello';
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded; charset=UTF-8';

        new Request(
            headers: new StubHeaderContext(),
            cookies: new StubInputContext(),
            get: new StubInputContext(),
            post: new StubInputContext(),
            files: new StubUploadedFilesContext(),
            body: new StubBodyContext(),
            method: Method::QUERY,
        );

        self::assertSame(
            [
                'q' => 'hello',
            ],
            $_POST,
        );
    }
}
