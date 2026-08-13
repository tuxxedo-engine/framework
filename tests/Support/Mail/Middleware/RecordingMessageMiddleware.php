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

namespace Support\Mail\Middleware;

use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\Middleware\MailMiddlewareInterface;

class RecordingMessageMiddleware implements MailMiddlewareInterface
{
    /**
     * @var list<MessageInterface>
     */
    public array $seen = [];

    public function process(
        MessageInterface $message,
    ): MessageInterface {
        $this->seen[] = $message;

        return $message;
    }
}
