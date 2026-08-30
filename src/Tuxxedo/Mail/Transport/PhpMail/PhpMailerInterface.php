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

namespace Tuxxedo\Mail\Transport\PhpMail;

interface PhpMailerInterface
{
    public function send(
        string $to,
        string $subject,
        string $body,
        string $headers,
        ?string $envelopeFrom,
    ): bool;
}
