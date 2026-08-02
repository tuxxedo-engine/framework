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

namespace Tuxxedo\Mail\Transport;

use Tuxxedo\Mail\MailException;
use Tuxxedo\Mail\Result\SendResult;
use Tuxxedo\Mail\Serializer\SerializedMessageInterface;

interface MailTransportInterface
{
    /**
     * @throws MailException
     */
    public function send(
        SerializedMessageInterface ...$serialized,
    ): void;

    /**
     * @return list<SendResult>
     *
     * @throws MailException
     */
    public function sendWithResult(
        SerializedMessageInterface ...$serialized,
    ): array;
}
