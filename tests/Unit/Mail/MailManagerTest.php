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
use Support\Mail\Middleware\RecordingMessageMiddleware;
use Support\Mail\Middleware\RecordingWireMiddleware;
use Support\Mail\Serializer\StubMessageSerializer;
use Support\Mail\Transport\RecordingMailTransport;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\MailManager;
use Tuxxedo\Mail\Message;

class MailManagerTest extends TestCase
{
    private function makeMessage(
        string $subject = 'Test',
    ): Message {
        return new Message(
            from: new Address(
                email: 'from@example.com',
            ),
            to: [
                new Address(
                    email: 'to@example.com',
                ),
            ],
            subject: $subject,
        );
    }

    public function testSendForwardsSingleMessageToTransport(): void
    {
        $transport = new RecordingMailTransport();
        $manager = new MailManager(
            transport: $transport,
        );
        $message = $this->makeMessage();

        $manager->send($message);

        self::assertSame(
            [
                $message,
            ],
            $transport->sent,
        );
    }

    public function testSendForwardsMultiplePositionalMessagesToTransport(): void
    {
        $transport = new RecordingMailTransport();
        $manager = new MailManager(
            transport: $transport,
        );

        $first = $this->makeMessage(subject: 'first');
        $second = $this->makeMessage(subject: 'second');
        $third = $this->makeMessage(subject: 'third');

        $manager->send($first, $second, $third);

        self::assertSame(
            [
                $first,
                $second,
                $third,
            ],
            $transport->sent,
        );
    }

    public function testSendSpreadsIterableIntoTransport(): void
    {
        $transport = new RecordingMailTransport();
        $manager = new MailManager(
            transport: $transport,
        );

        $messages = [
            $this->makeMessage(subject: 'a'),
            $this->makeMessage(subject: 'b'),
        ];

        $manager->send(...$messages);

        self::assertSame($messages, $transport->sent);
    }

    public function testSendWithZeroMessagesIsANoop(): void
    {
        $transport = new RecordingMailTransport();
        $manager = new MailManager(
            transport: $transport,
        );

        $manager->send();

        self::assertSame(
            [],
            $transport->sent,
        );
        self::assertSame(0, $transport->sendCalls);
    }

    public function testSendWithResultWithZeroMessagesReturnsEmptyList(): void
    {
        $transport = new RecordingMailTransport();
        $manager = new MailManager(
            transport: $transport,
        );

        $results = $manager->sendWithResult();

        self::assertSame([], $results);
        self::assertSame(0, $transport->sendCalls);
    }

    public function testSendWithResultForwardsSingleMessage(): void
    {
        $transport = new RecordingMailTransport();
        $manager = new MailManager(
            transport: $transport,
        );
        $message = $this->makeMessage();

        $results = $manager->sendWithResult($message);

        self::assertCount(1, $results);
        self::assertSame(
            [
                $message,
            ],
            $transport->sent,
        );
    }

    public function testSendWithResultPreservesOrderAcrossMultipleMessages(): void
    {
        $transport = new RecordingMailTransport();
        $manager = new MailManager(
            transport: $transport,
        );

        $first = $this->makeMessage(subject: 'first');
        $second = $this->makeMessage(subject: 'second');

        $results = $manager->sendWithResult($first, $second);

        self::assertCount(2, $results);
        self::assertSame($first, $transport->sent[0]);
        self::assertSame($second, $transport->sent[1]);
    }

    public function testPipelineInvokesMessageMiddlewareInOrder(): void
    {
        $transport = new RecordingMailTransport();
        $first = new RecordingMessageMiddleware();
        $second = new RecordingMessageMiddleware();

        $manager = new MailManager(
            transport: $transport,
            messageMiddleware: [
                $first,
                $second,
            ],
        );

        $message = $this->makeMessage();

        $manager->send($message);

        self::assertSame(
            [
                $message,
            ],
            $first->seen,
        );
        self::assertSame(
            [
                $message,
            ],
            $second->seen,
        );
    }

    public function testPipelineInvokesWireMiddlewareAfterSerialization(): void
    {
        $transport = new RecordingMailTransport();
        $serializer = new StubMessageSerializer();
        $wire = new RecordingWireMiddleware();

        $manager = new MailManager(
            transport: $transport,
            serializer: $serializer,
            wireMiddleware: [
                $wire,
            ],
        );

        $message = $this->makeMessage();

        $manager->send($message);

        self::assertSame(
            [
                $message,
            ],
            $serializer->seen,
        );
        self::assertCount(1, $wire->seen);
        self::assertSame(
            $message,
            $wire->seen[0]->source,
        );
    }
}
