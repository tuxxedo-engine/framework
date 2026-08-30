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

namespace Support\Mail;

use Tuxxedo\Mail\Transport\PhpMail\PhpMailerInterface;

class RecordingPhpMailer implements PhpMailerInterface
{
    /**
     * @var list<array{to: string, subject: string, body: string, headers: string, envelopeFrom: string|null}>
     */
    public array $sent = [];

    public function __construct(
        public bool $deliveryResult = true,
    ) {
    }

    public function send(
        string $to,
        string $subject,
        string $body,
        string $headers,
        ?string $envelopeFrom,
    ): bool {
        $this->sent[] = [
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'headers' => $headers,
            'envelopeFrom' => $envelopeFrom,
        ];

        return $this->deliveryResult;
    }
}
