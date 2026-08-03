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

namespace Tuxxedo\Mail\Transport\FileMail;

use Tuxxedo\Mail\AddressInterface;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\Result\RecipientOutcome;
use Tuxxedo\Mail\Result\RecipientStatus;
use Tuxxedo\Mail\Result\SendResult;
use Tuxxedo\Mail\Serializer\SerializedMessageInterface;
use Tuxxedo\Mail\Transport\FileMail\Config\FileMailTransportConfigInterface;
use Tuxxedo\Mail\Transport\MailTransportInterface;

class FileMailTransport implements MailTransportInterface
{
    public function __construct(
        private readonly FileMailTransportConfigInterface $config,
    ) {
    }

    public function send(
        SerializedMessageInterface ...$serialized,
    ): void {
        foreach ($serialized as $item) {
            $this->writeOne($item);
        }
    }

    public function sendWithResult(
        SerializedMessageInterface ...$serialized,
    ): array {
        $results = [];

        foreach ($serialized as $item) {
            $message = $item->source;
            $recipients = self::collectRecipients($message);

            try {
                $this->writeOne($item);

                $status = RecipientStatus::ACCEPTED;
                $summary = null;
            } catch (MailException $e) {
                $status = RecipientStatus::PERMANENT_FAILURE;
                $summary = $e->getMessage();
            }

            $outcomes = [];

            foreach ($recipients as $recipient) {
                $outcomes[] = new RecipientOutcome(
                    recipient: $recipient,
                    status: $status,
                    summary: $summary,
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
     * @throws MailException
     */
    private function writeOne(
        SerializedMessageInterface $serialized,
    ): void {
        $path = \rtrim($this->config->directory, '/\\') . \DIRECTORY_SEPARATOR . self::filenameFor($serialized->source);

        if (\file_put_contents($path, $serialized->wire) === false) {
            throw MailException::fromTransportFailure(
                transport: self::class,
            );
        }
    }

    private static function filenameFor(
        MessageInterface $message,
    ): string {
        $slug = \preg_replace('/[^A-Za-z0-9._-]+/', '-', $message->messageId) ?? '';
        $slug = \trim($slug, '-');

        if ($slug === '') {
            $slug = \bin2hex(\random_bytes(8));
        }

        return $slug . '.eml';
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
