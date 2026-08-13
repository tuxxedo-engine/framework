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

use Tuxxedo\Mail\Middleware\MailWireMiddlewareInterface;
use Tuxxedo\Mail\Serializer\SerializedMessageInterface;

class RecordingWireMiddleware implements MailWireMiddlewareInterface
{
    /**
     * @var list<SerializedMessageInterface>
     */
    public array $seen = [];

    public function process(
        SerializedMessageInterface $serialized,
    ): SerializedMessageInterface {
        $this->seen[] = $serialized;

        return $serialized;
    }
}
