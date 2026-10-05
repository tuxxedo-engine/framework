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

namespace Unit\View\Lumi\Library\Standard\Function;

use PHPUnit\Framework\TestCase;
use Tuxxedo\File\File;
use Tuxxedo\Mail\Attachment;
use Tuxxedo\View\Lumi\Library\Standard\Function\MailFunctions;
use Tuxxedo\View\Lumi\Runtime\RuntimeException;

class MailFunctionsTest extends TestCase
{
    private MailFunctions $functions;

    protected function setUp(): void
    {
        $this->functions = new MailFunctions();
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
            $this->functions->hasAttachment(
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
            $this->functions->hasAttachment(
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
            $this->functions->hasAttachment(
                attachments: [],
                name: 'signature',
            ),
        );
    }

    public function testHasAttachmentSkipsNonAttachmentEntries(): void
    {
        self::assertTrue(
            $this->functions->hasAttachment(
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
            $this->functions->inlineImage(
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
            $this->functions->inlineImage(
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
            $this->functions->inlineImage(
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

        $this->functions->inlineImage(
            attachments: [],
            name: 'nope',
        );
    }
}
