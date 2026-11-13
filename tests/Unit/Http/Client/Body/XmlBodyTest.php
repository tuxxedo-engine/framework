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

namespace Unit\Http\Client\Body;

use Fixture\Http\Request\Context\BodyContextFixture;
use PHPUnit\Framework\TestCase;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Http\Client\Body\XmlBody;

class XmlBodyTest extends TestCase
{
    public function testEncodesObjects(): void
    {
        $xml = new XmlBody(new BodyContextFixture('John', 30))->encode(SerializerManagerFactory::create());

        self::assertStringContainsString('<name>John</name>', $xml);
        self::assertStringContainsString('<age>30</age>', $xml);
    }

    public function testContentType(): void
    {
        $value = new BodyContextFixture();

        self::assertSame('application/xml', new XmlBody($value)->contentType);
        self::assertSame('application/atom+xml', new XmlBody($value, contentType: 'application/atom+xml')->contentType);
    }
}
