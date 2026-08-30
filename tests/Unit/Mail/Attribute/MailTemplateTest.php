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

namespace Unit\Mail\Attribute;

use PHPUnit\Framework\TestCase;
use Support\Mail\StubTemplateMessage;
use Tuxxedo\Mail\Attribute\MailTemplate;
use Tuxxedo\Mail\BodyType;
use Tuxxedo\Mail\Message;

class MailTemplateTest extends TestCase
{
    public function testExtractFromReturnsNullForClassWithoutAttribute(): void
    {
        self::assertNull(
            MailTemplate::extractFrom(
                target: Message::class,
            ),
        );
    }

    public function testExtractFromReturnsInstanceForClassWithAttribute(): void
    {
        $attribute = MailTemplate::extractFrom(
            target: StubTemplateMessage::class,
        );

        self::assertInstanceOf(
            MailTemplate::class,
            $attribute,
        );

        self::assertSame(
            'stub',
            $attribute->name,
        );

        self::assertSame(
            BodyType::HTML,
            $attribute->bodyType,
        );
    }

    public function testExtractFromWorksOnObjectInstance(): void
    {
        $attribute = MailTemplate::extractFrom(
            target: new StubTemplateMessage(),
        );

        self::assertInstanceOf(
            MailTemplate::class,
            $attribute,
        );

        self::assertSame(
            'stub',
            $attribute->name,
        );
    }

    public function testBodyTypeDefaultsToHtml(): void
    {
        $attribute = new MailTemplate(
            name: 'foo',
        );

        self::assertSame(
            BodyType::HTML,
            $attribute->bodyType,
        );
    }
}
