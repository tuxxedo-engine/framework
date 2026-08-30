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

/**
 * @codeCoverageIgnore
 */
class NativePhpMailer implements PhpMailerInterface
{
    public function send(
        string $to,
        string $subject,
        string $body,
        string $headers,
        ?string $envelopeFrom,
    ): bool {
        if ($envelopeFrom === null) {
            return @\mail($to, $subject, $body, $headers);
        }

        $previousSendmailFrom = \ini_get('sendmail_from');

        \ini_set('sendmail_from', $envelopeFrom);

        try {
            return @\mail($to, $subject, $body, $headers, '-f ' . \escapeshellarg($envelopeFrom));
        } finally {
            if ($previousSendmailFrom === false) {
                \ini_restore('sendmail_from');
            } else {
                \ini_set('sendmail_from', $previousSendmailFrom);
            }
        }
    }
}
