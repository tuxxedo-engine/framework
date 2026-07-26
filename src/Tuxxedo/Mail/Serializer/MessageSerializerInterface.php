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

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\MessageInterface;

#[DefaultImplementation(class: MessageSerializer::class, lifecycle: Lifecycle::SINGLETON)]
interface MessageSerializerInterface
{
    /**
     * @throws MailException
     */
    #[\NoDiscard]
    public function serialize(
        MessageInterface $message,
    ): SerializedMessageInterface;
}
