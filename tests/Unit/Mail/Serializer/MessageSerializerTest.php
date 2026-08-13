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

namespace Unit\Mail\Serializer;

use PHPUnit\Framework\TestCase;
use Support\File\InMemoryFile;
use Tuxxedo\File\FileException;
use Tuxxedo\File\FileInterface;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\Attachment;
use Tuxxedo\Mail\BodyType;
use Tuxxedo\Mail\Header;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\Message;
use Tuxxedo\Mail\Serializer\MessageSerializer;
use Tuxxedo\Mail\Serializer\SerializedMessage;

class MessageSerializerTest extends TestCase
{
    private function serializer(): MessageSerializer
    {
        return new MessageSerializer();
    }

    private function baseMessage(
        ?string $body = 'hello',
        BodyType $bodyType = BodyType::TEXT,
        ?string $alternativeText = null,
        string $subject = 'Test Subject',
    ): Message {
        return new Message(
            from: new Address(
                email: 'sender@example.com',
            ),
            to: [
                new Address(
                    email: 'recipient@example.com',
                ),
            ],
            subject: $subject,
            body: $body,
            bodyType: $bodyType,
            alternativeText: $alternativeText,
        );
    }

    public function testPlainTextSinglePartEmitsSevenBitTextPlain(): void
    {
        $result = $this->serializer()->serialize(
            $this->baseMessage(),
        );

        self::assertStringContainsString('Content-Type: text/plain; charset=UTF-8', $result->headers);
        self::assertStringContainsString('Content-Transfer-Encoding: 7bit', $result->headers);
        self::assertStringContainsString('MIME-Version: 1.0', $result->headers);
        self::assertStringNotContainsString('boundary=', $result->headers);
        self::assertSame("hello", $result->body);
    }

    public function testHtmlSinglePartEmitsTextHtml(): void
    {
        $result = $this->serializer()->serialize(
            $this->baseMessage(
                body: '<p>Hello</p>',
                bodyType: BodyType::HTML,
            ),
        );

        self::assertStringContainsString('Content-Type: text/html; charset=UTF-8', $result->headers);
        self::assertStringNotContainsString('multipart/', $result->headers);
    }

    public function testBodyWithNonAsciiSelectsEightBit(): void
    {
        $result = $this->serializer()->serialize(
            $this->baseMessage(
                body: 'hejsan Ålborg',
            ),
        );

        self::assertStringContainsString('Content-Transfer-Encoding: 8bit', $result->headers);
    }

    public function testBodyWithLongLineSelectsQuotedPrintable(): void
    {
        $result = $this->serializer()->serialize(
            $this->baseMessage(
                body: \str_repeat('x', 1200),
            ),
        );

        self::assertStringContainsString('Content-Transfer-Encoding: quoted-printable', $result->headers);
    }

    public function testHtmlPlusAlternativeTextBuildsMultipartAlternative(): void
    {
        $result = $this->serializer()->serialize(
            $this->baseMessage(
                body: '<p>rich</p>',
                bodyType: BodyType::HTML,
                alternativeText: 'plain fallback',
            ),
        );

        self::assertStringContainsString('Content-Type: multipart/alternative;', $result->headers);
        self::assertStringContainsString('boundary="', $result->headers);
        self::assertStringContainsString('Content-Type: text/plain; charset=UTF-8', $result->body);
        self::assertStringContainsString('Content-Type: text/html; charset=UTF-8', $result->body);
        self::assertStringContainsString('plain fallback', $result->body);
    }

    public function testAttachmentBuildsMultipartMixed(): void
    {
        $message = new Message(
            from: new Address(
                email: 'sender@example.com',
            ),
            to: [
                new Address(
                    email: 'recipient@example.com',
                ),
            ],
            subject: 'With attachment',
            body: 'body text',
            attachments: [
                Attachment::attachment(
                    file: new InMemoryFile(
                        bytes: 'binary-payload',
                        name: 'doc.pdf',
                        mimeType: 'application/pdf',
                    ),
                ),
            ],
        );

        $result = $this->serializer()->serialize($message);

        self::assertStringContainsString('Content-Type: multipart/mixed; boundary="', $result->headers);
        self::assertStringContainsString('Content-Type: text/plain; charset=UTF-8', $result->body);
        self::assertStringContainsString('Content-Type: application/pdf', $result->body);
        self::assertStringContainsString('Content-Disposition: attachment; filename="doc.pdf"', $result->body);
        self::assertStringContainsString(\base64_encode('binary-payload'), $result->body);
    }

    public function testHtmlPlusAlternativePlusAttachmentNestsAlternativeInMixed(): void
    {
        $message = new Message(
            from: new Address(
                email: 'sender@example.com',
            ),
            to: [
                new Address(
                    email: 'recipient@example.com',
                ),
            ],
            subject: 'Nested',
            body: '<p>rich</p>',
            bodyType: BodyType::HTML,
            alternativeText: 'plain fallback',
            attachments: [
                Attachment::attachment(
                    file: new InMemoryFile(
                        bytes: 'stuff',
                        name: 'a.txt',
                        mimeType: 'text/plain',
                    ),
                ),
            ],
        );

        $result = $this->serializer()->serialize($message);

        self::assertStringContainsString('Content-Type: multipart/mixed', $result->headers);
        self::assertStringContainsString('Content-Type: multipart/alternative', $result->body);
        self::assertStringContainsString('Content-Disposition: attachment; filename="a.txt"', $result->body);
    }

    public function testInlineAttachmentWrapsInMultipartRelated(): void
    {
        $message = new Message(
            from: new Address(
                email: 'sender@example.com',
            ),
            to: [
                new Address(
                    email: 'recipient@example.com',
                ),
            ],
            subject: 'Inline',
            body: '<p>see <img src="cid:pic"></p>',
            bodyType: BodyType::HTML,
            attachments: [
                Attachment::inline(
                    file: new InMemoryFile(
                        bytes: 'PNGDATA',
                        name: 'pic.png',
                        mimeType: 'image/png',
                    ),
                    contentId: 'pic',
                ),
            ],
        );

        $result = $this->serializer()->serialize($message);

        self::assertStringContainsString('Content-Type: multipart/related; boundary="', $result->headers);
        self::assertStringContainsString('Content-ID: <pic>', $result->body);
        self::assertStringContainsString('Content-Disposition: inline; filename="pic.png"', $result->body);
    }

    public function testAllRecipientHeadersEmittedExceptBcc(): void
    {
        $message = new Message(
            from: new Address(
                email: 'from@example.com',
                displayName: 'Sender Name',
            ),
            to: [
                new Address(
                    email: 'to1@example.com',
                ),
                new Address(
                    email: 'to2@example.com',
                ),
            ],
            subject: 'Recipients',
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
            replyTo: [
                new Address(
                    email: 'reply@example.com',
                ),
            ],
            sender: new Address(
                email: 'actual@example.com',
            ),
            returnPath: new Address(
                email: 'bounces@example.com',
            ),
        );

        $result = $this->serializer()->serialize($message);

        self::assertStringContainsString('From: "Sender Name" <from@example.com>', $result->headers);
        self::assertStringContainsString('Sender: actual@example.com', $result->headers);
        self::assertStringContainsString('Reply-To: reply@example.com', $result->headers);
        self::assertStringContainsString('Return-Path: bounces@example.com', $result->headers);
        self::assertStringContainsString('To: to1@example.com, to2@example.com', $result->headers);
        self::assertStringContainsString('Cc: cc@example.com', $result->headers);
        self::assertStringNotContainsString('bcc@example.com', $result->headers);
    }

    public function testNonAsciiSubjectEmitsEncodedWord(): void
    {
        $result = $this->serializer()->serialize(
            $this->baseMessage(
                subject: 'Grüße aus München',
            ),
        );

        self::assertMatchesRegularExpression('/Subject: =\?UTF-8\?B\?/', $result->headers);
    }

    public function testNonAsciiDisplayNameEncodesAsEncodedWord(): void
    {
        $message = new Message(
            from: new Address(
                email: 'ake@example.com',
                displayName: 'Åke',
            ),
            to: [
                new Address(
                    email: 'to@example.com',
                ),
            ],
            subject: 'test',
            body: 'body',
        );

        $result = $this->serializer()->serialize($message);

        self::assertMatchesRegularExpression('/From: =\?UTF-8\?B\?[A-Za-z0-9+\/=]+\?= <ake@example\.com>/', $result->headers);
    }

    public function testIdnDomainIsAsciiEncoded(): void
    {
        $message = new Message(
            from: new Address(
                email: 'user@münchen.example',
            ),
            to: [
                new Address(
                    email: 'to@example.com',
                ),
            ],
            subject: 'idn',
            body: 'body',
        );

        $result = $this->serializer()->serialize($message);

        self::assertStringContainsString('user@xn--mnchen-3ya.example', $result->headers);
    }

    public function testLongExtraHeaderIsFolded(): void
    {
        $longValue = \str_repeat('a', 200);

        $message = new Message(
            from: new Address(
                email: 'sender@example.com',
            ),
            to: [
                new Address(
                    email: 'recipient@example.com',
                ),
            ],
            subject: 'fold',
            body: 'body',
            extraHeaders: [
                new Header('X-Custom', $longValue),
            ],
        );

        $result = $this->serializer()->serialize($message);

        self::assertMatchesRegularExpression('/X-Custom:\r\n\ta+/', $result->headers);
    }

    public function testExtraHeaderCanOverrideContentType(): void
    {
        $message = new Message(
            from: new Address(
                email: 'sender@example.com',
            ),
            to: [
                new Address(
                    email: 'recipient@example.com',
                ),
            ],
            subject: 'override',
            body: 'body',
            extraHeaders: [
                new Header('Content-Type', 'application/x-custom'),
            ],
        );

        $result = $this->serializer()->serialize($message);

        self::assertStringContainsString('Content-Type: application/x-custom', $result->headers);
        self::assertStringNotContainsString('Content-Type: text/plain', $result->headers);
    }

    public function testSerializedMessageWireGetterConcatenates(): void
    {
        $wire = new SerializedMessage(
            source: $this->baseMessage(),
            headers: 'Subject: t',
            body: 'body-x',
        );

        self::assertSame("Subject: t\r\n\r\nbody-x", $wire->wire);
    }

    public function testExtraHeaderCanOverrideContentTransferEncoding(): void
    {
        $message = new Message(
            from: new Address(
                email: 'sender@example.com',
            ),
            to: [
                new Address(
                    email: 'recipient@example.com',
                ),
            ],
            subject: 'cte-override',
            body: 'body',
            extraHeaders: [
                new Header('Content-Transfer-Encoding', 'base64'),
            ],
        );

        $result = $this->serializer()->serialize($message);

        self::assertStringContainsString('Content-Transfer-Encoding: base64', $result->headers);
        self::assertStringNotContainsString('Content-Transfer-Encoding: 7bit', $result->headers);
    }

    public function testAttachmentReadFailureIsWrappedAsMailException(): void
    {
        $throwingFile = new class () implements FileInterface {
            public ?string $name {
                get {
                    return 'broken.dat';
                }
            }

            public ?string $mimeType {
                get {
                    return 'application/octet-stream';
                }
            }

            public ?int $size {
                get {
                    return null;
                }
            }

            public function contents(): string
            {
                throw new FileException('read failed');
            }
        };

        $message = new Message(
            from: new Address(
                email: 'sender@example.com',
            ),
            to: [
                new Address(
                    email: 'recipient@example.com',
                ),
            ],
            subject: 'broken',
            body: 'body',
            attachments: [
                Attachment::attachment(
                    file: $throwingFile,
                ),
            ],
        );

        try {
            (void) $this->serializer()->serialize($message);

            self::fail('Expected MailException');
        } catch (MailException $exception) {
            self::assertStringContainsString('broken.dat', $exception->getMessage());
            self::assertInstanceOf(FileException::class, $exception->getPrevious());
        }
    }

    public function testAttachmentDescriptionIsEmittedAsHeader(): void
    {
        $message = new Message(
            from: new Address(
                email: 'sender@example.com',
            ),
            to: [
                new Address(
                    email: 'recipient@example.com',
                ),
            ],
            subject: 'desc',
            body: 'body',
            attachments: [
                Attachment::attachment(
                    file: new InMemoryFile(
                        bytes: 'x',
                        name: 'r.txt',
                        mimeType: 'text/plain',
                    ),
                    description: 'the report',
                ),
            ],
        );

        $result = $this->serializer()->serialize($message);

        self::assertStringContainsString('Content-Description: the report', $result->body);
    }

    public function testNonAsciiFilenameEmitsExtendedFilenameParameter(): void
    {
        $message = new Message(
            from: new Address(
                email: 'sender@example.com',
            ),
            to: [
                new Address(
                    email: 'recipient@example.com',
                ),
            ],
            subject: 'filename',
            body: 'body',
            attachments: [
                Attachment::attachment(
                    file: new InMemoryFile(
                        bytes: 'x',
                        name: 'åke.pdf',
                        mimeType: 'application/pdf',
                    ),
                ),
            ],
        );

        $result = $this->serializer()->serialize($message);

        self::assertStringContainsString('filename*=UTF-8\'\'', $result->body);
        self::assertStringContainsString(\rawurlencode('åke.pdf'), $result->body);
    }

    public function testLongNonAsciiSubjectChunksEncodedWord(): void
    {
        $subject = \str_repeat('ä', 200);

        $result = $this->serializer()->serialize(
            $this->baseMessage(
                subject: $subject,
            ),
        );

        $encodedWordCount = \preg_match_all('/=\?UTF-8\?B\?[A-Za-z0-9+\/=]+\?=/', $result->headers);

        self::assertGreaterThan(1, $encodedWordCount);
    }
}
