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

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Mail\Config\MailManagerConfigInterface;
use Tuxxedo\Mail\Middleware\MailMiddlewareInterface;
use Tuxxedo\Mail\Middleware\MailWireMiddlewareInterface;
use Tuxxedo\Mail\Serializer\MessageSerializer;
use Tuxxedo\Mail\Serializer\MessageSerializerInterface;
use Tuxxedo\Mail\Transport\MailTransportInterface;

class MailManagerConfigurator implements MailManagerConfiguratorInterface
{
    public private(set) ?MailTransportInterface $transport = null;
    public private(set) MessageSerializerInterface $serializer;

    /**
     * @var list<MailMiddlewareInterface>
     */
    public private(set) array $messageMiddleware = [];

    /**
     * @var list<MailWireMiddlewareInterface>
     */
    public private(set) array $wireMiddleware = [];

    public function __construct(
        ?MessageSerializerInterface $serializer = null,
    ) {
        $this->serializer = $serializer ?? new MessageSerializer();
    }

    public static function fromConfig(
        ContainerInterface $container,
    ): self {
        $config = $container->resolve(MailManagerConfigInterface::class);

        $self = new self();
        $self->transport = $config->transport->createTransport($container);

        return $self;
    }

    public function withTransport(
        MailTransportInterface $transport,
    ): self {
        $this->transport = $transport;

        return $this;
    }

    public function withSerializer(
        MessageSerializerInterface $serializer,
    ): self {
        $this->serializer = $serializer;

        return $this;
    }

    public function withMessageMiddleware(
        MailMiddlewareInterface $middleware,
    ): self {
        $this->messageMiddleware[] = $middleware;

        return $this;
    }

    public function withWireMiddleware(
        MailWireMiddlewareInterface $middleware,
    ): self {
        $this->wireMiddleware[] = $middleware;

        return $this;
    }

    public function build(): MailManagerInterface
    {
        if ($this->transport === null) {
            throw MailException::fromMailManagerConfiguratorMissingTransport();
        }

        return new MailManager(
            transport: $this->transport,
            serializer: $this->serializer,
            messageMiddleware: $this->messageMiddleware,
            wireMiddleware: $this->wireMiddleware,
        );
    }
}
