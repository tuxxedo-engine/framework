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

use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\Serializer\SerializedMessageInterface;
use Tuxxedo\Mail\Transport\MailTransportInterface;

class PhpMailTransport implements MailTransportInterface
{
    public function send(
        SerializedMessageInterface ...$serialized,
    ): void {
        foreach ($serialized as $item) {
            $this->sendOne($item);
        }
    }

    /**
     * @throws MailException
     */
    private function sendOne(
        SerializedMessageInterface $serialized,
    ): void {
        $message = $serialized->source;

        if ($message->bcc !== []) {
            throw MailException::fromBccNotSupportedByTransport(self::class);
        }

        [
            $to,
            $subject,
            $strippedHeaders,
        ] = self::extractRoutingHeaders($serialized->headers);

        $envelopeFrom = $message->returnPath?->email;
        $delivered = self::invokeMail(
            to: $to,
            subject: $subject,
            body: $serialized->body,
            headers: $strippedHeaders,
            envelopeFrom: $envelopeFrom,
        );

        if (!$delivered) {
            throw MailException::fromTransportFailure(self::class);
        }
    }

    /**
     * @return array{string, string, string}
     */
    private static function extractRoutingHeaders(
        string $headers,
    ): array {
        $unfolded = \preg_replace('/\r\n([ \t])/', '$1', $headers) ?? $headers;
        $lines = \explode("\r\n", $unfolded);

        $to = '';
        $subject = '';
        $rest = [];

        foreach ($lines as $line) {
            if (\stripos($line, 'To: ') === 0) {
                $to = \substr($line, 4);
            } elseif (\stripos($line, 'Subject: ') === 0) {
                $subject = \substr($line, 9);
            } else {
                $rest[] = $line;
            }
        }

        return [
            $to,
            $subject,
            \implode("\r\n", $rest),
        ];
    }

    private static function invokeMail(
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
