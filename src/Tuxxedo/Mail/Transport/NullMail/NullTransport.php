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

namespace Tuxxedo\Mail\Transport\NullMail;

use Tuxxedo\Mail\AddressInterface;
use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\Result\RecipientOutcome;
use Tuxxedo\Mail\Result\RecipientStatus;
use Tuxxedo\Mail\Result\SendResult;
use Tuxxedo\Mail\Serializer\SerializedMessageInterface;
use Tuxxedo\Mail\Transport\MailTransportInterface;

class NullTransport implements MailTransportInterface
{
    public function send(
        SerializedMessageInterface ...$serialized,
    ): void {
    }

    public function sendWithResult(
        SerializedMessageInterface ...$serialized,
    ): array {
        $results = [];

        foreach ($serialized as $item) {
            $message = $item->source;
            $outcomes = [];

            foreach (self::collectRecipients($message) as $recipient) {
                $outcomes[] = new RecipientOutcome(
                    recipient: $recipient,
                    status: RecipientStatus::ACCEPTED,
                );
            }

            $results[] = new SendResult(
                message: $message,
                outcomes: $outcomes,
            );
        }

        return $results;
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

        foreach ($message->bcc as $recipient) {
            $recipients[] = $recipient;
        }

        return $recipients;
    }
}
