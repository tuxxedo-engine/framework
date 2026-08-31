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

namespace Support\Mail\Transport\SmtpMail;

use Tuxxedo\Mail\Transport\SmtpMail\SmtpResponse;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpSocketInterface;
use Tuxxedo\Mail\Transport\SmtpMail\SmtpTls;

class ScriptedSmtpSocket implements SmtpSocketInterface
{
    public bool $isConnected = false;
    public bool $cryptoEnabled = false;

    /**
     * @var list<string>
     */
    public array $writtenCommands = [];

    /**
     * @var list<string>
     */
    public array $writtenRaw = [];

    /**
     * @var list<SmtpResponse>
     */
    private array $scriptedResponses;

    public int $connectCalls = 0;
    public int $disconnectCalls = 0;

    /**
     * @param list<SmtpResponse> $scriptedResponses
     */
    public function __construct(
        array $scriptedResponses = [],
    ) {
        $this->scriptedResponses = $scriptedResponses;
    }

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
        $this->connectCalls++;

        $this->isConnected = true;
    }

    public function enableCrypto(): void
    {
        $this->cryptoEnabled = true;
    }

    public function writeCommand(
        string $command,
    ): void {
        $this->writtenCommands[] = $command;
    }

    public function writeRaw(
        string $bytes,
    ): void {
        $this->writtenRaw[] = $bytes;
    }

    #[\NoDiscard]
    public function readResponse(): SmtpResponse
    {
        if ($this->scriptedResponses === []) {
            throw new \RuntimeException(
                message: 'ScriptedSmtpSocket: no scripted response left; test may be under-scripted',
            );
        }

        return \array_shift($this->scriptedResponses);
    }

    public function disconnect(): void
    {
        $this->disconnectCalls++;

        $this->isConnected = false;
    }

    /**
     * @param list<SmtpResponse> $responses
     */
    public function queue(
        array $responses,
    ): void {
        foreach ($responses as $response) {
            $this->scriptedResponses[] = $response;
        }
    }
}
