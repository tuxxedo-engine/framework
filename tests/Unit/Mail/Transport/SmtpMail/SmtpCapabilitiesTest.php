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

namespace Unit\Mail\Transport\SmtpMail;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpCapabilities;

class SmtpCapabilitiesTest extends TestCase
{
    public function testSupportsReturnsTrueForKnownFeature(): void
    {
        $capabilities = new SmtpCapabilities(
            features: [
                'STARTTLS' => [],
                'SIZE' => [
                    '10485760',
                ],
            ],
        );

        self::assertTrue($capabilities->supports('STARTTLS'));
        self::assertTrue($capabilities->supports('SIZE'));
    }

    public function testSupportsIsCaseInsensitive(): void
    {
        $capabilities = new SmtpCapabilities(
            features: [
                'STARTTLS' => [],
            ],
        );

        self::assertTrue($capabilities->supports('starttls'));
    }

    public function testSupportsReturnsFalseForUnknownFeature(): void
    {
        $capabilities = new SmtpCapabilities(
            features: [
                'STARTTLS' => [],
            ],
        );

        self::assertFalse($capabilities->supports('PIPELINING'));
    }

    public function testGetParamsReturnsFeatureParameters(): void
    {
        $capabilities = new SmtpCapabilities(
            features: [
                'AUTH' => [
                    'PLAIN',
                    'LOGIN',
                ],
            ],
        );

        self::assertSame(
            [
                'PLAIN',
                'LOGIN',
            ],
            $capabilities->getParams('AUTH'),
        );
    }

    public function testGetParamsReturnsEmptyListForUnknownFeature(): void
    {
        $capabilities = new SmtpCapabilities(
            features: [],
        );

        self::assertSame(
            [],
            $capabilities->getParams('AUTH'),
        );
    }

    public function testParseBuildsCapabilitiesFromEhloLines(): void
    {
        $capabilities = SmtpCapabilities::parse(
            lines: [
                'mailpit.local hello',
                'SIZE 10485760',
                'AUTH PLAIN LOGIN',
                'STARTTLS',
            ],
        );

        self::assertTrue($capabilities->supports('SIZE'));
        self::assertSame(
            [
                '10485760',
            ],
            $capabilities->getParams('SIZE'),
        );
        self::assertSame(
            [
                'PLAIN',
                'LOGIN',
            ],
            $capabilities->getParams('AUTH'),
        );
        self::assertTrue($capabilities->supports('STARTTLS'));
    }

    public function testParseSkipsEmptyExtensionLines(): void
    {
        $capabilities = SmtpCapabilities::parse(
            lines: [
                'mailpit.local hello',
                '',
                'SIZE 10485760',
            ],
        );

        self::assertTrue($capabilities->supports('SIZE'));
    }
}
