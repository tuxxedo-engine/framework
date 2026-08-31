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
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpSocket;

class SmtpSocketTest extends TestCase
{
    public function testDisconnectIsNoopWhenNotConnected(): void
    {
        $socket = new SmtpSocket();

        $socket->disconnect();

        self::assertFalse($socket->isConnected);
    }

    public function testWriteCommandThrowsWhenNotConnected(): void
    {
        $socket = new SmtpSocket();

        $this->expectException(MailException::class);

        $socket->writeCommand(
            command: 'EHLO example.com',
        );
    }

    public function testReadResponseThrowsWhenNotConnected(): void
    {
        $socket = new SmtpSocket();

        $this->expectException(MailException::class);

        (void) $socket->readResponse();
    }

    public function testEnableCryptoThrowsWhenNotConnected(): void
    {
        $socket = new SmtpSocket();

        $this->expectException(MailException::class);

        $socket->enableCrypto();
    }
}
