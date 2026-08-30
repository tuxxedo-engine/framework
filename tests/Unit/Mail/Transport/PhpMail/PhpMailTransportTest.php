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

namespace Unit\Mail\Transport\PhpMail;

use PHPUnit\Framework\TestCase;
use Support\Mail\RecordingPhpMailer;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\Message;
use Tuxxedo\Mail\Result\RecipientStatus;
use Tuxxedo\Mail\Serializer\MessageSerializer;
use Tuxxedo\Mail\Serializer\SerializedMessageInterface;
use Tuxxedo\Mail\Transport\PhpMail\PhpMailTransport;

class PhpMailTransportTest extends TestCase
{
    private function serialize(
        Message $message,
    ): SerializedMessageInterface {
        return (new MessageSerializer())->serialize($message);
    }

    public function testSendPassesToAndSubjectToMailer(): void
    {
        $mailer = new RecordingPhpMailer();
        $transport = new PhpMailTransport(
            mailer: $mailer,
        );

        $serialized = $this->serialize(
            new Message(
                from: new Address(
                    email: 'from@example.com',
                ),
                to: 'to@example.com',
                subject: 'Hello',
                body: 'body',
            ),
        );

        $transport->send($serialized);

        self::assertCount(1, $mailer->sent);
        self::assertSame(
            'to@example.com',
            $mailer->sent[0]['to'],
        );
        self::assertSame(
            'Hello',
            $mailer->sent[0]['subject'],
        );
    }

    public function testSendPassesBodyToMailer(): void
    {
        $mailer = new RecordingPhpMailer();
        $transport = new PhpMailTransport(
            mailer: $mailer,
        );

        $serialized = $this->serialize(
            new Message(
                from: new Address(
                    email: 'from@example.com',
                ),
                to: 'to@example.com',
                subject: 'Subject',
                body: 'the body',
            ),
        );

        $transport->send($serialized);

        self::assertStringContainsString(
            'the body',
            $mailer->sent[0]['body'],
        );
    }

    public function testSendStripsToAndSubjectFromHeaders(): void
    {
        $mailer = new RecordingPhpMailer();
        $transport = new PhpMailTransport(
            mailer: $mailer,
        );

        $serialized = $this->serialize(
            new Message(
                from: new Address(
                    email: 'from@example.com',
                ),
                to: 'to@example.com',
                subject: 'Hi',
                body: 'body',
            ),
        );

        $transport->send($serialized);

        self::assertStringNotContainsString(
            'To: to@example.com',
            $mailer->sent[0]['headers'],
        );
        self::assertStringNotContainsString(
            'Subject: Hi',
            $mailer->sent[0]['headers'],
        );
    }

    public function testSendPassesReturnPathAsEnvelopeFrom(): void
    {
        $mailer = new RecordingPhpMailer();
        $transport = new PhpMailTransport(
            mailer: $mailer,
        );

        $serialized = $this->serialize(
            new Message(
                from: new Address(
                    email: 'from@example.com',
                ),
                to: 'to@example.com',
                subject: 'Envelope',
                body: 'body',
                returnPath: new Address(
                    email: 'bounces@example.com',
                ),
            ),
        );

        $transport->send($serialized);

        self::assertSame(
            'bounces@example.com',
            $mailer->sent[0]['envelopeFrom'],
        );
    }

    public function testSendPassesNullEnvelopeFromWhenNoReturnPath(): void
    {
        $mailer = new RecordingPhpMailer();
        $transport = new PhpMailTransport(
            mailer: $mailer,
        );

        $serialized = $this->serialize(
            new Message(
                from: new Address(
                    email: 'from@example.com',
                ),
                to: 'to@example.com',
                subject: 'Subject',
                body: 'body',
            ),
        );

        $transport->send($serialized);

        self::assertNull(
            $mailer->sent[0]['envelopeFrom'],
        );
    }

    public function testSendThrowsWhenMailerReturnsFalse(): void
    {
        $mailer = new RecordingPhpMailer(
            deliveryResult: false,
        );

        $transport = new PhpMailTransport(
            mailer: $mailer,
        );

        $serialized = $this->serialize(
            new Message(
                from: new Address(
                    email: 'from@example.com',
                ),
                to: 'to@example.com',
                subject: 'Fail',
                body: 'body',
            ),
        );

        $this->expectException(MailException::class);

        $transport->send($serialized);
    }

    public function testSendThrowsWhenMessageHasBccRecipients(): void
    {
        $mailer = new RecordingPhpMailer();
        $transport = new PhpMailTransport(
            mailer: $mailer,
        );

        $serialized = $this->serialize(
            new Message(
                from: new Address(
                    email: 'from@example.com',
                ),
                to: 'to@example.com',
                subject: 'BCC',
                body: 'body',
                bcc: 'bcc@example.com',
            ),
        );

        $this->expectException(MailException::class);

        $transport->send($serialized);
    }

    public function testSendWithResultReturnsAcceptedOutcomeForEachRecipient(): void
    {
        $mailer = new RecordingPhpMailer();
        $transport = new PhpMailTransport(
            mailer: $mailer,
        );

        $serialized = $this->serialize(
            new Message(
                from: new Address(
                    email: 'from@example.com',
                ),
                to: [
                    'a@example.com',
                    'b@example.com',
                ],
                subject: 'Multi',
                body: 'body',
                cc: 'c@example.com',
            ),
        );

        $results = $transport->sendWithResult($serialized);

        self::assertCount(1, $results);
        self::assertCount(3, $results[0]->outcomes);

        foreach ($results[0]->outcomes as $outcome) {
            self::assertSame(
                RecipientStatus::ACCEPTED,
                $outcome->status,
            );
        }
    }

    public function testSendWithResultReturnsFailureOutcomeWhenMailerReturnsFalse(): void
    {
        $mailer = new RecordingPhpMailer(
            deliveryResult: false,
        );
        $transport = new PhpMailTransport(
            mailer: $mailer,
        );

        $serialized = $this->serialize(
            new Message(
                from: new Address(
                    email: 'from@example.com',
                ),
                to: 'to@example.com',
                subject: 'Fail',
                body: 'body',
            ),
        );

        $results = $transport->sendWithResult($serialized);

        self::assertCount(1, $results);
        self::assertCount(1, $results[0]->outcomes);
        self::assertSame(
            RecipientStatus::PERMANENT_FAILURE,
            $results[0]->outcomes[0]->status,
        );
        self::assertNotNull(
            $results[0]->outcomes[0]->summary,
        );
    }

    public function testSendWithResultWithMultipleSerializedMessagesReturnsResultPerMessage(): void
    {
        $mailer = new RecordingPhpMailer();
        $transport = new PhpMailTransport(
            mailer: $mailer,
        );

        $first = $this->serialize(
            new Message(
                from: new Address(
                    email: 'from@example.com',
                ),
                to: 'one@example.com',
                subject: 'First',
                body: 'body',
            ),
        );

        $second = $this->serialize(
            new Message(
                from: new Address(
                    email: 'from@example.com',
                ),
                to: 'two@example.com',
                subject: 'Second',
                body: 'body',
            ),
        );

        $results = $transport->sendWithResult($first, $second);

        self::assertCount(2, $results);
    }
}
