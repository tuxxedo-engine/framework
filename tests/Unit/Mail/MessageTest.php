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
use Support\File\InMemoryFile;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\Attachment;
use Tuxxedo\Mail\BodyType;
use Tuxxedo\Mail\Header;
use Tuxxedo\Mail\MailException;
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

    private function baseMessage(): Message
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
            subject: 'original',
            body: 'original body',
        );
    }

    private function makeAttachment(
        string $name,
    ): Attachment {
        return Attachment::attachment(
            file: new InMemoryFile(
                bytes: 'bytes',
                name: $name,
            ),
        );
    }

    public function testWithFromReplacesAddressAndPreservesOtherFields(): void
    {
        $original = $this->baseMessage();

        $updated = $original->withFrom(
            from: 'new@example.com',
        );

        self::assertNotSame($original, $updated);
        self::assertSame('from@example.com', $original->from->email);
        self::assertSame('new@example.com', $updated->from->email);
        self::assertSame($original->subject, $updated->subject);
        self::assertSame($original->to, $updated->to);
    }

    public function testWithToReplacesRecipientList(): void
    {
        $updated = $this->baseMessage()->withTo(
            to: [
                'a@example.com',
                'b@example.com',
            ],
        );

        self::assertCount(2, $updated->to);
        self::assertSame('a@example.com', $updated->to[0]->email);
        self::assertSame('b@example.com', $updated->to[1]->email);
    }

    public function testWithSubjectReplacesSubjectAndReturnsNewInstance(): void
    {
        $original = $this->baseMessage();

        $updated = $original->withSubject(
            subject: 'changed',
        );

        self::assertNotSame($original, $updated);
        self::assertSame('original', $original->subject);
        self::assertSame('changed', $updated->subject);
    }

    public function testWithBodyRebuildsBodyPartsForHtmlWithAlternativeText(): void
    {
        $updated = $this->baseMessage()->withBody(
            body: '<p>Hi</p>',
            bodyType: BodyType::HTML,
            alternativeText: 'Hi',
        );

        self::assertSame('<p>Hi</p>', $updated->body);
        self::assertSame(BodyType::HTML, $updated->bodyType);
        self::assertSame('Hi', $updated->alternativeText);
    }

    public function testWithBodyRejectsAlternativeTextWhenBodyTypeIsNotHtml(): void
    {
        try {
            $this->baseMessage()->withBody(
                body: 'plain',
                bodyType: BodyType::TEXT,
                alternativeText: 'not allowed here',
            );

            self::fail('Expected MailException was not thrown');
        } catch (MailException $exception) {
            self::assertStringContainsString('alternativetext', \strtolower($exception->getMessage()));
        }
    }

    public function testWithCcBccReplyToReplaceTheirRespectiveLists(): void
    {
        $updated = $this->baseMessage()
            ->withCc(cc: 'cc@example.com')
            ->withBcc(bcc: 'bcc@example.com')
            ->withReplyTo(replyTo: 'reply@example.com');

        self::assertCount(1, $updated->cc);
        self::assertSame('cc@example.com', $updated->cc[0]->email);
        self::assertCount(1, $updated->bcc);
        self::assertSame('bcc@example.com', $updated->bcc[0]->email);
        self::assertCount(1, $updated->replyTo);
        self::assertSame('reply@example.com', $updated->replyTo[0]->email);
    }

    public function testWithSenderSetsAndClears(): void
    {
        $message = $this->baseMessage();

        $withSender = $message->withSender(
            sender: 'agent@example.com',
        );

        self::assertNotNull($withSender->sender);
        self::assertSame('agent@example.com', $withSender->sender->email);

        $cleared = $withSender->withSender(
            sender: null,
        );

        self::assertNull($cleared->sender);
    }

    public function testWithReturnPathSetsAndClears(): void
    {
        $message = $this->baseMessage();

        $withPath = $message->withReturnPath(
            returnPath: 'bounces@example.com',
        );

        self::assertNotNull($withPath->returnPath);
        self::assertSame('bounces@example.com', $withPath->returnPath->email);

        $cleared = $withPath->withReturnPath(
            returnPath: null,
        );

        self::assertNull($cleared->returnPath);
    }

    public function testWithAttachmentAppendsToExistingList(): void
    {
        $first = $this->makeAttachment(name: 'a.txt');
        $second = $this->makeAttachment(name: 'b.txt');

        $message = $this->baseMessage()
            ->withAttachment(attachment: $first)
            ->withAttachment(attachment: $second);

        self::assertCount(2, $message->attachments);
        self::assertSame($first, $message->attachments[0]);
        self::assertSame($second, $message->attachments[1]);
    }

    public function testWithAttachmentsReplacesExistingList(): void
    {
        $prior = $this->makeAttachment(name: 'old.txt');
        $replacement = $this->makeAttachment(name: 'new.txt');

        $message = $this->baseMessage()
            ->withAttachment(attachment: $prior)
            ->withAttachments(attachments: [
                $replacement,
            ]);

        self::assertCount(1, $message->attachments);
        self::assertSame($replacement, $message->attachments[0]);
    }

    public function testWithExtraHeaderAppendsToExistingList(): void
    {
        $first = new Header(
            name: 'X-A',
            value: '1',
        );
        $second = new Header(
            name: 'X-B',
            value: '2',
        );

        $message = $this->baseMessage()
            ->withExtraHeader(header: $first)
            ->withExtraHeader(header: $second);

        self::assertCount(2, $message->extraHeaders);
        self::assertSame($first, $message->extraHeaders[0]);
        self::assertSame($second, $message->extraHeaders[1]);
    }

    public function testWithExtraHeadersReplacesExistingList(): void
    {
        $prior = new Header(
            name: 'X-Prior',
            value: 'old',
        );
        $replacement = new Header(
            name: 'X-New',
            value: 'new',
        );

        $message = $this->baseMessage()
            ->withExtraHeader(header: $prior)
            ->withExtraHeaders(extraHeaders: [
                $replacement,
            ]);

        self::assertCount(1, $message->extraHeaders);
        self::assertSame($replacement, $message->extraHeaders[0]);
    }

    public function testWithExtraHeaderRejectsReservedHeaderName(): void
    {
        try {
            $this->baseMessage()->withExtraHeader(
                header: new Header(
                    name: 'Subject',
                    value: 'trying to override',
                ),
            );

            self::fail('Expected MailException was not thrown');
        } catch (MailException $exception) {
            self::assertStringContainsString('reserved', \strtolower($exception->getMessage()));
        }
    }

    public function testWithExtraHeadersRejectsReservedHeaderNameInList(): void
    {
        try {
            $this->baseMessage()->withExtraHeaders(
                extraHeaders: [
                    new Header(
                        name: 'X-Ok',
                        value: 'fine',
                    ),
                    new Header(
                        name: 'From',
                        value: 'nope',
                    ),
                ],
            );

            self::fail('Expected MailException was not thrown');
        } catch (MailException $exception) {
            self::assertStringContainsString('reserved', \strtolower($exception->getMessage()));
        }
    }

    public function testConstructorRejectsReservedHeaderNameInInitialExtraHeaders(): void
    {
        try {
            new Message(
                from: new Address(
                    email: 'from@example.com',
                ),
                to: [
                    new Address(
                        email: 'to@example.com',
                    ),
                ],
                subject: 'test',
                extraHeaders: [
                    new Header(
                        name: 'Date',
                        value: 'nope',
                    ),
                ],
            );

            self::fail('Expected MailException was not thrown');
        } catch (MailException $exception) {
            self::assertStringContainsString('reserved', \strtolower($exception->getMessage()));
        }
    }

    public function testWithoutExtraHeaderRemovesMatchByCaseInsensitiveName(): void
    {
        $message = $this->baseMessage()
            ->withExtraHeader(
                header: new Header(
                    name: 'X-Campaign',
                    value: 'summer',
                ),
            )
            ->withExtraHeader(
                header: new Header(
                    name: 'X-Trace',
                    value: 'abc',
                ),
            );

        $filtered = $message->withoutExtraHeader(
            name: 'x-campaign',
        );

        self::assertCount(1, $filtered->extraHeaders);
        self::assertSame('X-Trace', $filtered->extraHeaders[0]->name);
    }

    public function testWithoutExtraHeaderIsANoopWhenNoHeaderMatches(): void
    {
        $message = $this->baseMessage()->withExtraHeader(
            header: new Header(
                name: 'X-Campaign',
                value: 'summer',
            ),
        );

        $unchanged = $message->withoutExtraHeader(
            name: 'X-Nonexistent',
        );

        self::assertCount(1, $unchanged->extraHeaders);
        self::assertSame('X-Campaign', $unchanged->extraHeaders[0]->name);
    }
}
