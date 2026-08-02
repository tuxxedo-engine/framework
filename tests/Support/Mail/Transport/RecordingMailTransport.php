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

namespace Support\Mail\Transport;

use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\Result\RecipientOutcome;
use Tuxxedo\Mail\Result\RecipientStatus;
use Tuxxedo\Mail\Result\SendResult;
use Tuxxedo\Mail\Serializer\SerializedMessageInterface;
use Tuxxedo\Mail\Transport\MailTransportInterface;

class RecordingMailTransport implements MailTransportInterface
{
    /**
     * @var list<MessageInterface>
     */
    public array $sent = [];

    /**
     * @var list<SerializedMessageInterface>
     */
    public array $sentSerialized = [];

    public int $sendCalls = 0;

    public function send(
        SerializedMessageInterface ...$serialized,
    ): void {
        $this->sendCalls++;

        foreach ($serialized as $item) {
            $this->sent[] = $item->source;
            $this->sentSerialized[] = $item;
        }
    }

    public function sendWithResult(
        SerializedMessageInterface ...$serialized,
    ): array {
        $this->sendCalls++;
        $results = [];

        foreach ($serialized as $item) {
            $this->sent[] = $item->source;
            $this->sentSerialized[] = $item;

            $outcomes = [];

            foreach ($item->source->to as $recipient) {
                $outcomes[] = new RecipientOutcome(
                    recipient: $recipient,
                    status: RecipientStatus::ACCEPTED,
                );
            }

            $results[] = new SendResult(
                message: $item->source,
                outcomes: $outcomes,
            );
        }

        return $results;
    }
}
