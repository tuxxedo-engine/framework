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

namespace Support\Mail\Serializer;

use Tuxxedo\Mail\MessageInterface;
use Tuxxedo\Mail\Serializer\MessageSerializerInterface;
use Tuxxedo\Mail\Serializer\SerializedMessage;
use Tuxxedo\Mail\Serializer\SerializedMessageInterface;

class StubMessageSerializer implements MessageSerializerInterface
{
    /**
     * @var list<MessageInterface>
     */
    public array $seen = [];

    public function serialize(
        MessageInterface $message,
    ): SerializedMessageInterface {
        $this->seen[] = $message;

        return new SerializedMessage(
            source: $message,
            headers: 'Subject: stubbed',
            body: 'stubbed body',
        );
    }
}
