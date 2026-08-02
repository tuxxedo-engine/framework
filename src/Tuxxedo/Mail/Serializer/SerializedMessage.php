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

namespace Tuxxedo\Mail\Serializer;

use Tuxxedo\Mail\MessageInterface;

class SerializedMessage implements SerializedMessageInterface
{
    public string $wire {
        get {
            return $this->headers . "\r\n\r\n" . $this->body;
        }
    }

    public function __construct(
        public readonly MessageInterface $source,
        public readonly string $headers,
        public readonly string $body,
    ) {
    }
}
