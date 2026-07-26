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

namespace Unit\Mail;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\BodyType;
use Tuxxedo\Mail\Message;

class MessageTest extends TestCase
{
    public function testConstructsWithMinimalFields(): void
    {
        $from = new Address(
            email: 'from@example.com',
        );

        $to = new Address(
            email: 'to@example.com',
        );

        $message = new Message(
            from: $from,
            to: [
                $to,
            ],
            subject: 'Welcome',
        );

        self::assertSame($from, $message->from);
        self::assertSame(
            [
                $to,
            ],
            $message->to,
        );

        self::assertSame('Welcome', $message->subject);
        self::assertNull($message->body);
        self::assertSame(BodyType::TEXT, $message->bodyType);
        self::assertNull($message->alternativeText);
        self::assertSame(
            [],
            $message->extraHeaders,
        );

        self::assertSame(
            [],
            $message->cc,
        );

        self::assertSame(
            [],
            $message->bcc,
        );

        self::assertSame(
            [],
            $message->replyTo,
        );

        self::assertNull($message->sender);
        self::assertNull($message->returnPath);
    }

    public function testConstructsWithAllRecipientLists(): void
    {
        $from = new Address(
            email: 'from@example.com',
        );

        $to = new Address(
            email: 'to@example.com',
        );

        $cc = new Address(
            email: 'cc@example.com',
        );

        $bcc = new Address(
            email: 'bcc@example.com',
        );

        $replyTo = new Address(
            email: 'reply@example.com',
        );

        $sender = new Address(
            email: 'sender@example.com',
        );

        $returnPath = new Address(
            email: 'bounce@example.com',
        );

        $message = new Message(
            from: $from,
            to: [
                $to,
            ],
            subject: 'Broad recipients',
            cc: [
                $cc,
            ],
            bcc: [
                $bcc,
            ],
            replyTo: [
                $replyTo,
            ],
            sender: $sender,
            returnPath: $returnPath,
        );

        self::assertSame(
            [
                $cc,
            ],
            $message->cc,
        );

        self::assertSame(
            [
                $bcc,
            ],
            $message->bcc,
        );

        self::assertSame(
            [
                $replyTo,
            ],
            $message->replyTo,
        );

        self::assertSame($sender, $message->sender);
        self::assertSame($returnPath, $message->returnPath);
    }

    public function testStoresHtmlBodyWithAlternativeText(): void
    {
        $message = new Message(
            from: new Address(
                email: 'from@example.com',
            ),
            to: [
                new Address(
                    email: 'to@example.com',
                ),
            ],
            subject: 'Body',
            body: '<p>Hello there.</p>',
            bodyType: BodyType::HTML,
            alternativeText: 'Hello there.',
        );

        self::assertSame('<p>Hello there.</p>', $message->body);
        self::assertSame(BodyType::HTML, $message->bodyType);
        self::assertSame('Hello there.', $message->alternativeText);
    }

    public function testAutoGeneratesMessageIdUsingFromDomain(): void
    {
        $message = new Message(
            from: new Address(
                email: 'noreply@example.com',
            ),
            to: [
                new Address(
                    email: 'to@example.com',
                ),
            ],
            subject: 'auto id',
        );

        self::assertMatchesRegularExpression(
            '/^<[a-f0-9]{32}@example\.com>$/',
            $message->messageId,
        );
    }

    public function testAutoGeneratesUniqueMessageIdPerMessage(): void
    {
        $from = new Address(
            email: 'from@example.com',
        );

        $to = new Address(
            email: 'to@example.com',
        );

        $first = new Message(
            from: $from,
            to: [
                $to,
            ],
            subject: 'a',
        );

        $second = new Message(
            from: $from,
            to: [
                $to,
            ],
            subject: 'b',
        );

        self::assertNotSame($first->messageId, $second->messageId);
    }

    public function testExplicitMessageIdIsPreserved(): void
    {
        $message = new Message(
            from: new Address(
                email: 'from@example.com',
            ),
            to: [
                new Address(
                    email: 'to@example.com',
                ),
            ],
            subject: 'explicit',
            messageId: '<explicit-id@example.com>',
        );

        self::assertSame(
            '<explicit-id@example.com>',
            $message->messageId,
        );
    }

    public function testAutoGeneratesDateCloseToNow(): void
    {
        $before = new \DateTimeImmutable();
        $message = new Message(
            from: new Address(
                email: 'from@example.com',
            ),
            to: [
                new Address(
                    email: 'to@example.com',
                ),
            ],
            subject: 'auto date',
        );

        $after = new \DateTimeImmutable();

        self::assertGreaterThanOrEqual($before, $message->date);
        self::assertLessThanOrEqual($after, $message->date);
    }

    public function testExplicitDateIsPreserved(): void
    {
        $explicit = new \DateTimeImmutable('2026-01-15 12:00:00');
        $message = new Message(
            from: new Address(
                email: 'from@example.com',
            ),
            to: [
                new Address(
                    email: 'to@example.com',
                ),
            ],
            subject: 'explicit date',
            date: $explicit,
        );

        self::assertSame($explicit, $message->date);
    }
}
