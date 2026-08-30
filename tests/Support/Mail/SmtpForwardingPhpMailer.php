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

class SmtpForwardingPhpMailer implements PhpMailerInterface
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $ehloDomain = 'phpmailer.test',
        private readonly float $connectTimeout = 5.0,
    ) {
    }

    public function send(
        string $to,
        string $subject,
        string $body,
        string $headers,
        ?string $envelopeFrom,
    ): bool {
        $recipients = self::extractRecipients($to);
        $mailFrom = $envelopeFrom ?? self::extractHeader($headers, 'From') ?? 'unknown@localhost';

        $errno = 0;
        $errstr = '';

        $socket = @\fsockopen(
            hostname: $this->host,
            port: $this->port,
            error_code: $errno,
            error_message: $errstr,
            timeout: $this->connectTimeout,
        );

        if ($socket === false) {
            return false;
        }

        try {
            $this->readResponse($socket, 220);
            $this->sendLine($socket, 'EHLO ' . $this->ehloDomain);
            $this->readResponse($socket, 250);
            $this->sendLine($socket, 'MAIL FROM:<' . $mailFrom . '>');
            $this->readResponse($socket, 250);

            foreach ($recipients as $recipient) {
                $this->sendLine($socket, 'RCPT TO:<' . $recipient . '>');
                $this->readResponse($socket, 250);
            }

            $this->sendLine($socket, 'DATA');
            $this->readResponse($socket, 354);

            $payload = 'To: ' . $to . "\r\n"
                . 'Subject: ' . $subject . "\r\n"
                . $headers . "\r\n"
                . "\r\n"
                . self::dotStuff($body);

            \fwrite($socket, $payload . "\r\n.\r\n");
            $this->readResponse($socket, 250);

            $this->sendLine($socket, 'QUIT');
        } finally {
            \fclose($socket);
        }

        return true;
    }

    /**
     * @param resource $socket
     */
    private function sendLine(
        $socket,
        string $line,
    ): void {
        \fwrite($socket, $line . "\r\n");
    }

    /**
     * @param resource $socket
     */
    private function readResponse(
        $socket,
        int $expectedCode,
    ): void {
        do {
            $line = \fgets($socket);

            if ($line === false) {
                throw new \RuntimeException(
                    message: 'SMTP server closed the connection unexpectedly',
                );
            }

            $code = (int) \substr($line, 0, 3);
            $continuation = $line[3] ?? ' ';
        } while ($continuation === '-');

        if ($code !== $expectedCode) {
            throw new \RuntimeException(
                message: \sprintf(
                    'SMTP response expected %d but got %d: %s',
                    $expectedCode,
                    $code,
                    \trim($line),
                ),
            );
        }
    }

    /**
     * @return list<string>
     */
    private static function extractRecipients(
        string $to,
    ): array {
        $recipients = [];

        foreach (\explode(',', $to) as $part) {
            $part = \trim($part);

            if ($part === '') {
                continue;
            }

            if (\preg_match('/<([^>]+)>/', $part, $matches) === 1) {
                $recipients[] = $matches[1];

                continue;
            }

            $recipients[] = $part;
        }

        return $recipients;
    }

    private static function extractHeader(
        string $headers,
        string $name,
    ): ?string {
        $lines = \explode("\r\n", $headers);
        $prefix = $name . ': ';
        $prefixLength = \strlen($prefix);

        foreach ($lines as $line) {
            if (\stripos($line, $prefix) !== 0) {
                continue;
            }

            $value = \substr($line, $prefixLength);

            if (\preg_match('/<([^>]+)>/', $value, $matches) === 1) {
                return $matches[1];
            }

            return \trim($value);
        }

        return null;
    }

    private static function dotStuff(
        string $body,
    ): string {
        $lines = \explode("\r\n", $body);

        foreach ($lines as $index => $line) {
            if (\str_starts_with($line, '.')) {
                $lines[$index] = '.' . $line;
            }
        }

        return \implode("\r\n", $lines);
    }
}
