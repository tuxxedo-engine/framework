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

namespace Unit\View\Lumi\Library\Standard;

use PHPUnit\Framework\TestCase;
use Tuxxedo\File\File;
use Tuxxedo\Mail\Attachment;
use Tuxxedo\View\Lumi\Library\Standard\MailLibrary;
use Tuxxedo\View\Lumi\Runtime\RuntimeException;

class MailLibraryTest extends TestCase
{
    private MailLibrary $library;

    protected function setUp(): void
    {
        $this->library = new MailLibrary();
    }

    private function inline(
        string $contentId,
    ): Attachment {
        return Attachment::inline(
            file: new File(
                name: 'file.png',
                mimeType: 'image/png',
                bytes: 'x',
            ),
            contentId: $contentId,
        );
    }

    public function testHasAttachmentReturnsTrueWhenMatchingAttachmentPresent(): void
    {
        self::assertTrue(
            $this->library->hasAttachment(
                attachments: [
                    $this->inline('signature'),
                ],
                name: 'signature',
            ),
        );
    }

    public function testHasAttachmentReturnsFalseWhenNoMatchingAttachmentPresent(): void
    {
        self::assertFalse(
            $this->library->hasAttachment(
                attachments: [
                    $this->inline('logo'),
                ],
                name: 'signature',
            ),
        );
    }

    public function testHasAttachmentReturnsFalseForEmptyAttachmentList(): void
    {
        self::assertFalse(
            $this->library->hasAttachment(
                attachments: [],
                name: 'signature',
            ),
        );
    }

    public function testHasAttachmentSkipsNonAttachmentEntries(): void
    {
        self::assertTrue(
            $this->library->hasAttachment(
                attachments: [
                    'noise',
                    $this->inline('signature'),
                ],
                name: 'signature',
            ),
        );
    }

    public function testInlineImageReturnsImgTagForMatchingAttachment(): void
    {
        self::assertSame(
            '<img src="cid:signature" alt="">',
            $this->library->inlineImage(
                attachments: [
                    $this->inline('signature'),
                ],
                name: 'signature',
            ),
        );
    }

    public function testInlineImageEscapesAltText(): void
    {
        self::assertSame(
            '<img src="cid:signature" alt="&quot;hi&quot;">',
            $this->library->inlineImage(
                attachments: [
                    $this->inline('signature'),
                ],
                name: 'signature',
                alt: '"hi"',
            ),
        );
    }

    public function testInlineImageSkipsNonAttachmentAndNonMatchingEntries(): void
    {
        self::assertSame(
            '<img src="cid:signature" alt="">',
            $this->library->inlineImage(
                attachments: [
                    'noise',
                    $this->inline('logo'),
                    $this->inline('signature'),
                ],
                name: 'signature',
            ),
        );
    }

    public function testInlineImageThrowsWhenAttachmentMissing(): void
    {
        self::expectException(RuntimeException::class);

        $this->library->inlineImage(
            attachments: [],
            name: 'nope',
        );
    }
}
