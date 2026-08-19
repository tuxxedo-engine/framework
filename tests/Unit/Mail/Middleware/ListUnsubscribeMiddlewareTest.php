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

namespace Unit\Mail\Middleware;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\Header;
use Tuxxedo\Mail\HeaderInterface;
use Tuxxedo\Mail\Message;
use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\Middleware\ListUnsubscribeMiddleware;

class ListUnsubscribeMiddlewareTest extends TestCase
{
    private static function newMessage(): Message
    {
        return new Message(
            from: new Address(
                email: 'from@example.com',
            ),
            to: [
                new Address(
                    email: 'to@example.com',
                ),
            ],
            subject: 'Newsletter',
        );
    }

    /**
     * @param list<HeaderInterface> $headers
     */
    private static function findHeader(
        array $headers,
        string $name,
    ): ?HeaderInterface {
        foreach ($headers as $header) {
            if ($header->is($name)) {
                return $header;
            }
        }

        return null;
    }

    public function testAddsListUnsubscribeHeaderFromClosureReturnValue(): void
    {
        $middleware = new ListUnsubscribeMiddleware(
            unsubscribeUrlBuilder: static fn (MessageInterface $message): string => 'https://example.com/u/abc',
        );

        $result = $middleware->process(
            message: self::newMessage(),
        );

        $header = self::findHeader(
            headers: $result->extraHeaders,
            name: 'List-Unsubscribe',
        );

        self::assertNotNull($header);
        self::assertSame('<https://example.com/u/abc>', $header->value);
    }

    public function testOneClickHeaderIsAddedByDefault(): void
    {
        $middleware = new ListUnsubscribeMiddleware(
            unsubscribeUrlBuilder: static fn (MessageInterface $message): string => 'https://example.com/u/abc',
        );

        $result = $middleware->process(
            message: self::newMessage(),
        );

        $header = self::findHeader(
            headers: $result->extraHeaders,
            name: 'List-Unsubscribe-Post',
        );

        self::assertNotNull($header);
        self::assertSame('List-Unsubscribe=One-Click', $header->value);
    }

    public function testOneClickHeaderIsOmittedWhenDisabled(): void
    {
        $middleware = new ListUnsubscribeMiddleware(
            unsubscribeUrlBuilder: static fn (MessageInterface $message): string => 'https://example.com/u/abc',
            oneClick: false,
        );

        $result = $middleware->process(
            message: self::newMessage(),
        );

        self::assertNull(
            self::findHeader(
                headers: $result->extraHeaders,
                name: 'List-Unsubscribe-Post',
            ),
        );

        self::assertNotNull(
            self::findHeader(
                headers: $result->extraHeaders,
                name: 'List-Unsubscribe',
            ),
        );
    }

    public function testUrlBuilderReceivesTheMessageBeingProcessed(): void
    {
        $captured = null;

        $middleware = new ListUnsubscribeMiddleware(
            unsubscribeUrlBuilder: static function (MessageInterface $message) use (&$captured): string {
                $captured = $message;

                return 'https://example.com/u/xyz';
            },
        );

        $message = self::newMessage();
        $middleware->process(
            message: $message,
        );

        self::assertSame($message, $captured);
    }

    public function testExistingExtraHeadersArePreserved(): void
    {
        $original = self::newMessage()->withExtraHeader(
            new Header(
                name: 'X-Campaign-Id',
                value: 'summer-2026',
            ),
        );

        $middleware = new ListUnsubscribeMiddleware(
            unsubscribeUrlBuilder: static fn (MessageInterface $message): string => 'https://example.com/u/abc',
        );

        $result = $middleware->process(
            message: $original,
        );

        $campaign = self::findHeader(
            headers: $result->extraHeaders,
            name: 'X-Campaign-Id',
        );

        self::assertNotNull($campaign);
        self::assertSame('summer-2026', $campaign->value);

        self::assertNotNull(
            self::findHeader(
                headers: $result->extraHeaders,
                name: 'List-Unsubscribe',
            ),
        );

        self::assertNotNull(
            self::findHeader(
                headers: $result->extraHeaders,
                name: 'List-Unsubscribe-Post',
            ),
        );
    }

    public function testDoesNotMutateInputMessage(): void
    {
        $message = self::newMessage();

        $middleware = new ListUnsubscribeMiddleware(
            unsubscribeUrlBuilder: static fn (MessageInterface $incoming): string => 'https://example.com/u/abc',
        );

        $result = $middleware->process(
            message: $message,
        );

        self::assertNotSame($message, $result);
        self::assertSame(
            [],
            $message->extraHeaders,
        );
    }

    public function testUrlIsInsertedVerbatimIncludingQueryString(): void
    {
        $middleware = new ListUnsubscribeMiddleware(
            unsubscribeUrlBuilder: static fn (MessageInterface $message): string => 'https://example.com/u?token=abc&list=news',
        );

        $result = $middleware->process(
            message: self::newMessage(),
        );

        $header = self::findHeader(
            headers: $result->extraHeaders,
            name: 'List-Unsubscribe',
        );

        self::assertNotNull($header);
        self::assertSame('<https://example.com/u?token=abc&list=news>', $header->value);
    }
}
