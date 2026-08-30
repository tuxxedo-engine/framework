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
use Tuxxedo\Container\Container;
use Tuxxedo\Escaper\Escaper;
use Tuxxedo\File\File;
use Tuxxedo\Mail\Attachment;
use Tuxxedo\View\Lumi\Library\Standard\Function\InlineImageFunction;
use Tuxxedo\View\Lumi\Runtime\RuntimeException;

class InlineImageFunctionTest extends TestCase
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

    private function createFunction(): InlineImageFunction
    {
        return new InlineImageFunction(
            container: (new Container())->singleton(
                class: new Escaper(),
            ),
        );
    }

    public function testCallReturnsImgTagWithCidAndAlt(): void
    {
        $function = $this->createFunction();

        self::assertSame(
            '<img src="cid:signature" alt="Signature">',
            $function->call(
                [
                    [
                        $this->inline('signature'),
                    ],
                    'signature',
                    'Signature',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallDefaultsAltToEmptyStringWhenOmitted(): void
    {
        $function = $this->createFunction();

        self::assertSame(
            '<img src="cid:signature" alt="">',
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

    public function testCallEscapesAltAttributeValue(): void
    {
        $function = $this->createFunction();

        self::assertSame(
            '<img src="cid:logo" alt="&quot;A&amp;B&quot;">',
            $function->call(
                [
                    [
                        $this->inline('logo'),
                    ],
                    'logo',
                    '"A&B"',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallSkipsNonAttachmentValuesInList(): void
    {
        $function = $this->createFunction();

        self::assertSame(
            '<img src="cid:logo" alt="">',
            $function->call(
                [
                    [
                        'stray',
                        $this->inline('logo'),
                    ],
                    'logo',
                ],
                static fn () => new StubRuntimeContext(),
            ),
        );
    }

    public function testCallThrowsWhenNoMatchingAttachmentPresent(): void
    {
        $function = $this->createFunction();

        try {
            $function->call(
                [
                    [
                        $this->inline('logo'),
                    ],
                    'signature',
                ],
                static fn () => new StubRuntimeContext(),
            );

            self::fail('Expected RuntimeException');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString(
                'signature',
                $exception->getMessage(),
            );
        }
    }
}
