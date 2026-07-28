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

namespace Tuxxedo\Mail\Transport;

use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\Serializer\MessageSerializerInterface;

class PhpMailTransport implements MailTransportInterface
{
    public function __construct(
        private readonly MessageSerializerInterface $serializer,
    ) {
    }

    public function send(
        MessageInterface ...$messages,
    ): void {
        foreach ($messages as $message) {
            $this->sendOne($message);
        }
    }

    /**
     * @throws MailException
     */
    private function sendOne(
        MessageInterface $message,
    ): void {
        if ($message->bcc !== []) {
            throw MailException::fromBccNotSupportedByTransport(self::class);
        }

        $serialized = $this->serializer->serialize($message);

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

        $additionalParams = '-f ' . \escapeshellarg($envelopeFrom);
        $previousSendmailFrom = \ini_get('sendmail_from');

        \ini_set('sendmail_from', $envelopeFrom);

        try {
            return @\mail($to, $subject, $body, $headers, $additionalParams);
        } finally {
            if ($previousSendmailFrom === false) {
                \ini_restore('sendmail_from');
            } else {
                \ini_set('sendmail_from', $previousSendmailFrom);
            }
        }
    }
}
