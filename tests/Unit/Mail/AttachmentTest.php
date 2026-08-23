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
use Tuxxedo\File\FileInterface;
use Tuxxedo\Mail\Attachment;
use Tuxxedo\Mail\MailException;

class AttachmentTest extends TestCase
{
    private function file(): FileInterface
    {
        return new InMemoryFile(
            bytes: 'contents',
            name: 'image.png',
            mimeType: 'image/png',
        );
    }

    public function testInlineWrapsExplicitContentIdInAngleBrackets(): void
    {
        $attachment = Attachment::inline(
            file: $this->file(),
            contentId: 'cid-42',
        );

        self::assertSame('<cid-42>', $attachment->contentId);
    }

    public function testInlineIsIdempotentWhenContentIdAlreadyWrapped(): void
    {
        $attachment = Attachment::inline(
            file: $this->file(),
            contentId: '<cid-42>',
        );

        self::assertSame('<cid-42>', $attachment->contentId);
    }

    public function testInvalidContentIdWithControlCharsThrows(): void
    {
        try {
            (void) Attachment::inline(
                file: $this->file(),
                contentId: "corrupt\r\nheader",
            );

            self::fail('Expected MailException was not thrown');
        } catch (MailException $exception) {
            self::assertStringContainsString('content-id', \strtolower($exception->getMessage()));
        }
    }

    public function testInvalidDescriptionWithControlCharsThrows(): void
    {
        try {
            (void) Attachment::attachment(
                file: $this->file(),
                description: "corrupt\r\ninjection",
            );

            self::fail('Expected MailException was not thrown');
        } catch (MailException $exception) {
            self::assertStringContainsString('description', \strtolower($exception->getMessage()));
        }
    }

    public function testInlineWithoutContentIdAutoGeneratesOne(): void
    {
        $attachment = Attachment::inline(
            file: $this->file(),
        );

        self::assertNotNull($attachment->contentId);
        self::assertMatchesRegularExpression(
            '/^<[a-f0-9]{32}@[a-f0-9]{16}>$/',
            $attachment->contentId,
        );

        self::assertSame(8, $attachment->size);
    }
}
