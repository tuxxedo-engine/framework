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

namespace Unit\Mail\Result;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\BodyType;
use Tuxxedo\Mail\Message;
use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\Result\RecipientOutcome;
use Tuxxedo\Mail\Result\RecipientOutcomeInterface;
use Tuxxedo\Mail\Result\RecipientStatus;
use Tuxxedo\Mail\Result\SendResult;

class SendResultTest extends TestCase
{
    private static function newMessage(): MessageInterface
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
            subject: 'Result subject',
            body: 'body',
            bodyType: BodyType::TEXT,
        );
    }

    private static function outcome(
        string $email,
        RecipientStatus $status,
    ): RecipientOutcomeInterface {
        return new RecipientOutcome(
            recipient: new Address(
                email: $email,
            ),
            status: $status,
        );
    }

    /**
     * @param list<RecipientOutcomeInterface> $outcomes
     * @return list<string>
     */
    private static function recipientEmails(
        array $outcomes,
    ): array {
        return \array_map(
            static fn (RecipientOutcomeInterface $outcome): string => $outcome->recipient->email,
            $outcomes,
        );
    }

    public function testExposesMessagePassedIntoConstructor(): void
    {
        $message = self::newMessage();
        $result = new SendResult(
            message: $message,
            outcomes: [],
        );

        self::assertSame($message, $result->message);
    }

    public function testAllAcceptedProducesFullSuccess(): void
    {
        $result = new SendResult(
            message: self::newMessage(),
            outcomes: [
                self::outcome('a@example.com', RecipientStatus::ACCEPTED),
                self::outcome('b@example.com', RecipientStatus::ACCEPTED),
            ],
        );

        self::assertSame(2, $result->acceptedCount);
        self::assertSame(0, $result->failedCount);
        self::assertTrue($result->isFullSuccess);
        self::assertFalse($result->isPartialSuccess);
        self::assertFalse($result->isFailure);
    }

    public function testAllFailedProducesFailureAndNotPartial(): void
    {
        $result = new SendResult(
            message: self::newMessage(),
            outcomes: [
                self::outcome('a@example.com', RecipientStatus::PERMANENT_FAILURE),
                self::outcome('b@example.com', RecipientStatus::TRANSIENT_FAILURE),
            ],
        );

        self::assertSame(0, $result->acceptedCount);
        self::assertSame(2, $result->failedCount);
        self::assertFalse($result->isFullSuccess);
        self::assertFalse($result->isPartialSuccess);
        self::assertTrue($result->isFailure);
    }

    public function testMixedOutcomesProducePartialSuccess(): void
    {
        $result = new SendResult(
            message: self::newMessage(),
            outcomes: [
                self::outcome('a@example.com', RecipientStatus::ACCEPTED),
                self::outcome('b@example.com', RecipientStatus::TRANSIENT_FAILURE),
                self::outcome('c@example.com', RecipientStatus::PERMANENT_FAILURE),
            ],
        );

        self::assertSame(1, $result->acceptedCount);
        self::assertSame(2, $result->failedCount);
        self::assertFalse($result->isFullSuccess);
        self::assertTrue($result->isPartialSuccess);
        self::assertFalse($result->isFailure);
    }

    public function testAcceptedGetterReturnsOnlyAcceptedOutcomesInOrder(): void
    {
        $result = new SendResult(
            message: self::newMessage(),
            outcomes: [
                self::outcome('a@example.com', RecipientStatus::ACCEPTED),
                self::outcome('b@example.com', RecipientStatus::TRANSIENT_FAILURE),
                self::outcome('c@example.com', RecipientStatus::ACCEPTED),
                self::outcome('d@example.com', RecipientStatus::PERMANENT_FAILURE),
            ],
        );

        self::assertSame(
            [
                'a@example.com',
                'c@example.com',
            ],
            self::recipientEmails($result->accepted),
        );
    }

    public function testFailedGetterReturnsEveryNonAcceptedOutcomeInOrder(): void
    {
        $result = new SendResult(
            message: self::newMessage(),
            outcomes: [
                self::outcome('a@example.com', RecipientStatus::ACCEPTED),
                self::outcome('b@example.com', RecipientStatus::TRANSIENT_FAILURE),
                self::outcome('c@example.com', RecipientStatus::PERMANENT_FAILURE),
                self::outcome('d@example.com', RecipientStatus::ACCEPTED),
            ],
        );

        self::assertSame(
            [
                'b@example.com',
                'c@example.com',
            ],
            self::recipientEmails($result->failed),
        );
    }

    public function testTransientlyFailedGetterIsolatesTransientFailures(): void
    {
        $result = new SendResult(
            message: self::newMessage(),
            outcomes: [
                self::outcome('a@example.com', RecipientStatus::TRANSIENT_FAILURE),
                self::outcome('b@example.com', RecipientStatus::PERMANENT_FAILURE),
                self::outcome('c@example.com', RecipientStatus::TRANSIENT_FAILURE),
                self::outcome('d@example.com', RecipientStatus::ACCEPTED),
            ],
        );

        self::assertSame(
            [
                'a@example.com',
                'c@example.com',
            ],
            self::recipientEmails($result->transientlyFailed),
        );
    }

    public function testPermanentlyFailedGetterIsolatesPermanentFailures(): void
    {
        $result = new SendResult(
            message: self::newMessage(),
            outcomes: [
                self::outcome('a@example.com', RecipientStatus::PERMANENT_FAILURE),
                self::outcome('b@example.com', RecipientStatus::TRANSIENT_FAILURE),
                self::outcome('c@example.com', RecipientStatus::ACCEPTED),
                self::outcome('d@example.com', RecipientStatus::PERMANENT_FAILURE),
            ],
        );

        self::assertSame(
            [
                'a@example.com',
                'd@example.com',
            ],
            self::recipientEmails($result->permanentlyFailed),
        );
    }

    public function testEmptyOutcomesReportsBothFullSuccessAndFailure(): void
    {
        $result = new SendResult(
            message: self::newMessage(),
            outcomes: [],
        );

        self::assertSame(0, $result->acceptedCount);
        self::assertSame(0, $result->failedCount);
        self::assertTrue($result->isFullSuccess);
        self::assertFalse($result->isPartialSuccess);
        self::assertTrue($result->isFailure);
        self::assertSame(
            [],
            $result->accepted,
        );

        self::assertSame(
            [],
            $result->failed,
        );

        self::assertSame(
            [],
            $result->transientlyFailed,
        );

        self::assertSame(
            [],
            $result->permanentlyFailed,
        );
    }
}
