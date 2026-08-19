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

namespace Unit\Mail\Signer\Dkim;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Mail\Address;
use Tuxxedo\Mail\Message;
use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\Serializer\SerializedMessage;
use Tuxxedo\Mail\Signer\Dkim\DkimAlgorithm;
use Tuxxedo\Mail\Signer\Dkim\DkimCanonicalization;
use Tuxxedo\Mail\Signer\Dkim\DkimSignatureTag;
use Tuxxedo\Mail\Signer\Dkim\DkimSigningInput;

class DkimSigningInputTest extends TestCase
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
            subject: 'ignored',
        );
    }

    /**
     * @param list<string> $signedHeaders
     */
    private static function tag(
        array $signedHeaders,
        DkimCanonicalization $headerCanonicalization = DkimCanonicalization::RELAXED,
    ): DkimSignatureTag {
        return new DkimSignatureTag(
            algorithm: DkimAlgorithm::RSA_SHA256,
            headerCanonicalization: $headerCanonicalization,
            bodyCanonicalization: DkimCanonicalization::RELAXED,
            domain: 'example.com',
            selector: 'default',
            signedHeaders: $signedHeaders,
            bh: 'hash',
            b: '',
            timestamp: 1_700_000_000,
        );
    }

    private static function serialized(
        string $headers,
    ): SerializedMessage {
        return new SerializedMessage(
            source: self::newMessage(),
            headers: $headers,
            body: '',
        );
    }

    public function testBuildsRelaxedInputForSingleHeaderAndAppendsDkimLineWithoutTrailingCrlf(): void
    {
        $input = DkimSigningInput::build(
            serialized: self::serialized(
                headers: 'Subject: hello  world',
            ),
            tag: self::tag(
                signedHeaders: [
                    'Subject',
                ],
            ),
        );

        $expectedDkim = 'dkim-signature:v=1; a=rsa-sha256; c=relaxed/relaxed; d=example.com; s=default; h=Subject; bh=hash; t=1700000000; b=';

        self::assertSame(
            "subject:hello world\r\n" . $expectedDkim,
            $input,
        );
    }

    public function testFoldedHeaderContinuationLinesAttachToCurrentHeader(): void
    {
        $input = DkimSigningInput::build(
            serialized: self::serialized(
                headers: "Subject: hello\r\n there",
            ),
            tag: self::tag(
                signedHeaders: [
                    'Subject',
                ],
            ),
        );

        self::assertStringStartsWith("subject:hello there\r\n", $input);
    }

    public function testMultipleInstancesOfSameHeaderConsumeBottomUp(): void
    {
        $headers = "Received: from a\r\nReceived: from b\r\nReceived: from c";

        $input = DkimSigningInput::build(
            serialized: self::serialized(
                headers: $headers,
            ),
            tag: self::tag(
                signedHeaders: [
                    'Received',
                    'Received',
                ],
            ),
        );

        self::assertStringStartsWith(
            "received:from c\r\nreceived:from b\r\n",
            $input,
        );
    }

    public function testMissingSignedHeaderEmitsCanonicalizedPlaceholder(): void
    {
        $input = DkimSigningInput::build(
            serialized: self::serialized(
                headers: 'Subject: present',
            ),
            tag: self::tag(
                signedHeaders: [
                    'X-Absent',
                ],
            ),
        );

        self::assertStringStartsWith("x-absent:\r\n", $input);
    }

    public function testExhaustingMultipleInstancesFallsBackToPlaceholderForNextLookup(): void
    {
        $headers = "Received: from a\r\nReceived: from b";

        $input = DkimSigningInput::build(
            serialized: self::serialized(
                headers: $headers,
            ),
            tag: self::tag(
                signedHeaders: [
                    'Received',
                    'Received',
                    'Received',
                ],
            ),
        );

        self::assertStringStartsWith(
            "received:from b\r\nreceived:from a\r\nreceived:\r\n",
            $input,
        );
    }

    public function testSimpleCanonicalizationPreservesOriginalHeaderCasingAndSpacing(): void
    {
        $input = DkimSigningInput::build(
            serialized: self::serialized(
                headers: 'Subject:  Original   Casing',
            ),
            tag: self::tag(
                signedHeaders: [
                    'Subject',
                ],
                headerCanonicalization: DkimCanonicalization::SIMPLE,
            ),
        );

        self::assertStringStartsWith("Subject:  Original   Casing\r\n", $input);
    }

    public function testLineWithoutColonInHeaderBlockIsTreatedAsNamelessHeader(): void
    {
        $headers = "Broken\r\nSubject: real";

        $input = DkimSigningInput::build(
            serialized: self::serialized(
                headers: $headers,
            ),
            tag: self::tag(
                signedHeaders: [
                    'Broken',
                ],
            ),
        );

        self::assertStringStartsWith("broken\r\n", $input);
    }

    public function testEmptyLinesInHeaderBlockAreSkipped(): void
    {
        $headers = "Subject: hi\r\n\r\nX-Trailing: should-not-appear";

        $input = DkimSigningInput::build(
            serialized: self::serialized(
                headers: $headers,
            ),
            tag: self::tag(
                signedHeaders: [
                    'Subject',
                    'X-Trailing',
                ],
            ),
        );

        self::assertStringStartsWith(
            "subject:hi\r\nx-trailing:should-not-appear\r\n",
            $input,
        );
    }

    public function testDkimSignatureLineHasNoTrailingCrlf(): void
    {
        $input = DkimSigningInput::build(
            serialized: self::serialized(
                headers: 'Subject: hi',
            ),
            tag: self::tag(
                signedHeaders: [
                    'Subject',
                ],
            ),
        );

        self::assertStringEndsNotWith("\r\n", $input);
        self::assertStringContainsString('dkim-signature:', $input);
    }
}
