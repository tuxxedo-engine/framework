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
use Tuxxedo\Mail\Transport\SmtpMail\SmtpResponse;

class SmtpResponseTest extends TestCase
{
    public function testIsSuccessTrueForTwoHundredRange(): void
    {
        $response = new SmtpResponse(
            code: 250,
            lines: [
                '250 OK',
            ],
        );

        self::assertTrue($response->isSuccess);
        self::assertFalse($response->isIntermediate);
        self::assertFalse($response->isTransientFailure);
        self::assertFalse($response->isPermanentFailure);
    }

    public function testIsIntermediateTrueForThreeHundredRange(): void
    {
        $response = new SmtpResponse(
            code: 354,
            lines: [
                '354 Start mail input',
            ],
        );

        self::assertTrue($response->isIntermediate);
        self::assertFalse($response->isSuccess);
        self::assertFalse($response->isTransientFailure);
        self::assertFalse($response->isPermanentFailure);
    }

    public function testIsTransientFailureTrueForFourHundredRange(): void
    {
        $response = new SmtpResponse(
            code: 421,
            lines: [
                '421 Service not available',
            ],
        );

        self::assertTrue($response->isTransientFailure);
        self::assertFalse($response->isSuccess);
        self::assertFalse($response->isIntermediate);
        self::assertFalse($response->isPermanentFailure);
    }

    public function testIsPermanentFailureTrueForFiveHundredRange(): void
    {
        $response = new SmtpResponse(
            code: 550,
            lines: [
                '550 Mailbox unavailable',
            ],
        );

        self::assertTrue($response->isPermanentFailure);
        self::assertFalse($response->isSuccess);
        self::assertFalse($response->isIntermediate);
        self::assertFalse($response->isTransientFailure);
    }

    public function testSummaryReturnsFirstLine(): void
    {
        $response = new SmtpResponse(
            code: 250,
            lines: [
                '250-mailpit.local',
                '250-SIZE 10485760',
                '250 STARTTLS',
            ],
        );

        self::assertSame(
            '250-mailpit.local',
            $response->summary,
        );
    }

    public function testSummaryReturnsEmptyStringWhenLinesEmpty(): void
    {
        $response = new SmtpResponse(
            code: 250,
            lines: [],
        );

        self::assertSame(
            '',
            $response->summary,
        );
    }
}
