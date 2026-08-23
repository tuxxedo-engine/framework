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

namespace Unit\Mail\Transport\NullMail;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\Message;
use Tuxxedo\Mail\Result\RecipientStatus;
use Tuxxedo\Mail\Serializer\SerializedMessage;
use Tuxxedo\Mail\Transport\NullMail\NullTransport;

class NullTransportTest extends TestCase
{
    private function makeMessage(): Message
    {
        return new Message(
            from: new Address(
                email: 'sender@example.com',
            ),
            to: [
                new Address(
                    email: 'to1@example.com',
                ),
                new Address(
                    email: 'to2@example.com',
                ),
            ],
            subject: 'Test',
            body: 'body',
            cc: [
                new Address(
                    email: 'cc@example.com',
                ),
            ],
            bcc: [
                new Address(
                    email: 'bcc@example.com',
                ),
            ],
        );
    }

    private function serialize(
        Message $message,
    ): SerializedMessage {
        return new SerializedMessage(
            source: $message,
            headers: 'Subject: Test',
            body: 'body',
        );
    }

    public function testSendWithZeroMessagesIsANoop(): void
    {
        $this->expectNotToPerformAssertions();

        $transport = new NullTransport();

        $transport->send();
    }

    public function testSendWithMultipleMessagesDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();

        $transport = new NullTransport();

        $transport->send(
            $this->serialize($this->makeMessage()),
            $this->serialize($this->makeMessage()),
        );
    }

    public function testSendWithResultReturnsEmptyListForZeroMessages(): void
    {
        $transport = new NullTransport();

        $results = $transport->sendWithResult();

        self::assertSame(
            [],
            $results,
        );
    }

    public function testSendWithResultReturnsOneResultPerMessagePreservingOrder(): void
    {
        $transport = new NullTransport();

        $first = $this->makeMessage();
        $second = $this->makeMessage();

        $results = $transport->sendWithResult(
            $this->serialize($first),
            $this->serialize($second),
        );

        self::assertCount(2, $results);
        self::assertSame($first, $results[0]->message);
        self::assertSame($second, $results[1]->message);
    }

    public function testSendWithResultMarksEveryRecipientAcceptedWithoutSummary(): void
    {
        $transport = new NullTransport();

        $results = $transport->sendWithResult(
            $this->serialize($this->makeMessage()),
        );

        self::assertCount(1, $results);
        self::assertCount(4, $results[0]->outcomes);

        foreach ($results[0]->outcomes as $outcome) {
            self::assertSame(RecipientStatus::ACCEPTED, $outcome->status);
            self::assertNull($outcome->summary);
        }
    }

    public function testSendWithResultCollectsRecipientsInToCcBccOrder(): void
    {
        $transport = new NullTransport();

        $results = $transport->sendWithResult(
            $this->serialize($this->makeMessage()),
        );

        $emails = \array_map(
            static fn ($outcome): string => $outcome->recipient->email,
            $results[0]->outcomes,
        );

        self::assertSame(
            [
                'to1@example.com',
                'to2@example.com',
                'cc@example.com',
                'bcc@example.com',
            ],
            $emails,
        );
    }
}
