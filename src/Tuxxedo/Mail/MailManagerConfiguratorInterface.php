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

namespace Tuxxedo\Mail;

use Tuxxedo\Mail\Middleware\MailMiddlewareInterface;
use Tuxxedo\Mail\Middleware\MailWireMiddlewareInterface;
use Tuxxedo\Mail\Serializer\MessageSerializerInterface;
use Tuxxedo\Mail\Transport\MailTransportInterface;

interface MailManagerConfiguratorInterface
{
    public ?MailTransportInterface $transport {
        get;
    }

    public MessageSerializerInterface $serializer {
        get;
    }

    /**
     * @var list<MailMiddlewareInterface>
     */
    public array $messageMiddleware {
        get;
    }

    /**
     * @var list<MailWireMiddlewareInterface>
     */
    public array $wireMiddleware {
        get;
    }

    public function withTransport(
        MailTransportInterface $transport,
    ): self;

    public function withSerializer(
        MessageSerializerInterface $serializer,
    ): self;

    public function withMessageMiddleware(
        MailMiddlewareInterface $middleware,
    ): self;

    public function withWireMiddleware(
        MailWireMiddlewareInterface $middleware,
    ): self;

    /**
     * @throws MailException
     */
    public function build(): MailManagerInterface;
}
