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
use Tuxxedo\Mail\Transport\MailTransportInterface;

class RecordingMailTransport implements MailTransportInterface
{
    /**
     * @var list<MessageInterface>
     */
    public array $sent = [];

    public int $sendCalls = 0;

    public function send(
        MessageInterface ...$messages,
    ): void {
        $this->sendCalls++;

        foreach ($messages as $message) {
            $this->sent[] = $message;
        }
    }
}
