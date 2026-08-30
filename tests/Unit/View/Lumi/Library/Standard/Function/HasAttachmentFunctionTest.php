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
use Support\View\Lumi\Runtime\StubRuntimeContext;
use Tuxxedo\File\File;
use Tuxxedo\Mail\Attachment;
use Tuxxedo\View\Lumi\Library\Standard\Function\HasAttachmentFunction;

class HasAttachmentFunctionTest extends TestCase
{
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

    public function testCallReturnsTrueWhenMatchingAttachmentPresent(): void
    {
        $function = new HasAttachmentFunction();

        self::assertTrue(
            $function->call(
                [
                    [
                        $this->inline('signature'),
                    ],
                    'signature',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReturnsFalseWhenNoMatchingAttachmentPresent(): void
    {
        $function = new HasAttachmentFunction();

        self::assertFalse(
            $function->call(
                [
                    [
                        $this->inline('logo'),
                    ],
                    'signature',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallReturnsFalseForEmptyAttachmentList(): void
    {
        $function = new HasAttachmentFunction();

        self::assertFalse(
            $function->call(
                [
                    [],
                    'signature',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallSkipsNonAttachmentValuesInList(): void
    {
        $function = new HasAttachmentFunction();

        self::assertTrue(
            $function->call(
                [
                    [
                        'stray-string',
                        42,
                        $this->inline('signature'),
                    ],
                    'signature',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallDoesNotMatchAttachmentWithoutContentId(): void
    {
        $function = new HasAttachmentFunction();

        $plainAttachment = Attachment::attachment(
            file: new File(
                name: 'file.txt',
                mimeType: 'text/plain',
                bytes: 'x',
            ),
        );

        self::assertFalse(
            $function->call(
                [
                    [
                        $plainAttachment,
                    ],
                    'signature',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }
}
