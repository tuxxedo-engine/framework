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

use Tuxxedo\Mail\AddressInterface;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\Result\RecipientOutcome;
use Tuxxedo\Mail\Result\RecipientStatus;
use Tuxxedo\Mail\Result\SendResult;
use Tuxxedo\Mail\Serializer\SerializedMessageInterface;
use Tuxxedo\Mail\Transport\MailTransportInterface;

class PhpMailTransport implements MailTransportInterface
{
    public function __construct(
        private readonly PhpMailerInterface $mailer = new NativePhpMailer(),
    ) {
    }

    public function send(
        SerializedMessageInterface ...$serialized,
    ): void {
        foreach ($serialized as $item) {
            $this->sendOne($item);
        }
    }

    public function sendWithResult(
        SerializedMessageInterface ...$serialized,
    ): array {
        $results = [];

        foreach ($serialized as $item) {
            $results[] = $this->sendOneWithResult($item);
        }

        return $results;
    }

    private function sendOneWithResult(
        SerializedMessageInterface $serialized,
    ): SendResult {
        $message = $serialized->source;
        $recipients = self::collectRecipients($message);

        try {
            $this->sendOne($serialized);

            return new SendResult(
                message: $message,
                outcomes: self::outcomesForAll(
                    recipients: $recipients,
                    status: RecipientStatus::ACCEPTED,
                ),
            );
        } catch (MailException $e) {
            return new SendResult(
                message: $message,
                outcomes: self::outcomesForAll(
                    recipients: $recipients,
                    status: RecipientStatus::PERMANENT_FAILURE,
                    summary: $e->getMessage(),
                ),
            );
        }
    }

    /**
     * @return list<AddressInterface>
     */
    private static function collectRecipients(
        MessageInterface $message,
    ): array {
        $recipients = [];

        foreach ($message->to as $recipient) {
            $recipients[] = $recipient;
        }

        foreach ($message->cc as $recipient) {
            $recipients[] = $recipient;
        }

        return $recipients;
    }

    /**
     * @param list<AddressInterface> $recipients
     * @return list<RecipientOutcome>
     */
    private static function outcomesForAll(
        array $recipients,
        RecipientStatus $status,
        ?string $summary = null,
    ): array {
        $outcomes = [];

        foreach ($recipients as $recipient) {
            $outcomes[] = new RecipientOutcome(
                recipient: $recipient,
                status: $status,
                summary: $summary,
            );
        }

        return $outcomes;
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
        $delivered = $this->mailer->send(
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
            \join("\r\n", $rest),
        ];
    }
}
