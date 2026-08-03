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

namespace Tuxxedo\Mail\Transport\SmtpMail;

use Tuxxedo\Mail\MailException;

interface SmtpSocketInterface
{
    public bool $isConnected {
        get;
    }

    /**
     * @throws MailException
     */
    public function connect(
        string $host,
        int $port,
        SmtpTls $tls,
        int $connectTimeout,
        int $readTimeout,
        bool $verifyPeer,
        ?string $caFile,
        ?string $unixSocket = null,
    ): void;

    /**
     * @throws MailException
     */
    public function enableCrypto(): void;

    /**
     * @throws MailException
     */
    public function writeCommand(
        string $command,
    ): void;

    /**
     * @throws MailException
     */
    public function writeRaw(
        string $bytes,
    ): void;

    /**
     * @throws MailException
     */
    #[\NoDiscard]
    public function readResponse(): SmtpResponse;

    public function disconnect(): void;
}
