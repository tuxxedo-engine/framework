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

namespace Support\Mail\Transport;

use Tuxxedo\Mail\Transport\SmtpMail\SmtpResponse;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpSocketInterface;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpTls;

class StubSmtpSocket implements SmtpSocketInterface
{
    public bool $isConnected = false;

    public function connect(
        string $host,
        int $port,
        SmtpTls $tls,
        int $connectTimeout,
        int $readTimeout,
        bool $verifyPeer,
        ?string $caFile,
        ?string $unixSocket = null,
    ): void {
        $this->isConnected = true;
    }

    public function enableCrypto(): void
    {
    }

    public function writeCommand(
        string $command,
    ): void {
    }

    public function writeRaw(
        string $bytes,
    ): void {
    }

    public function readResponse(): SmtpResponse
    {
        return new SmtpResponse(
            code: 250,
            lines: [
                'OK',
            ],
        );
    }

    public function disconnect(): void
    {
        $this->isConnected = false;
    }
}
