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
use Tuxxedo\Container\DefaultInitializer;
use Tuxxedo\Mail\Config\MailManagerConfigInterface;
use Tuxxedo\Mail\Middleware\MailMiddlewareInterface;
use Tuxxedo\Mail\Middleware\MailWireMiddlewareInterface;
use Tuxxedo\Mail\Serializer\MessageSerializer;
use Tuxxedo\Mail\Serializer\MessageSerializerInterface;
use Tuxxedo\Mail\Serializer\SerializedMessageInterface;
use Tuxxedo\Mail\Transport\MailTransportInterface;

#[DefaultInitializer(
    static function (ContainerInterface $container): MailManagerInterface {
        $config = $container->resolve(MailManagerConfigInterface::class);

        return new MailManager(
            transport: $config->transport->createTransport($container),
        );
    },
)]
class MailManager implements MailManagerInterface
{
    /**
     * @param list<MailMiddlewareInterface> $messageMiddleware
     * @param list<MailWireMiddlewareInterface> $wireMiddleware
     */
    public function __construct(
        public readonly MailTransportInterface $transport,
        private readonly MessageSerializerInterface $serializer = new MessageSerializer(),
        private readonly array $messageMiddleware = [],
        private readonly array $wireMiddleware = [],
    ) {
    }

    public function send(
        MessageInterface ...$messages,
    ): void {
        if ($messages === []) {
            return;
        }

        $serialized = [];

        foreach ($messages as $message) {
            $serialized[] = $this->pipeline($message);
        }

        $this->transport->send(...$serialized);
    }

    public function sendWithResult(
        MessageInterface ...$messages,
    ): array {
        if ($messages === []) {
            return [];
        }

        $serialized = [];

        foreach ($messages as $message) {
            $serialized[] = $this->pipeline($message);
        }

        return $this->transport->sendWithResult(...$serialized);
    }

    /**
     * @throws MailException
     */
    private function pipeline(
        MessageInterface $message,
    ): SerializedMessageInterface {
        foreach ($this->messageMiddleware as $middleware) {
            $message = $middleware->process($message);
        }

        $serialized = $this->serializer->serialize($message);

        foreach ($this->wireMiddleware as $middleware) {
            $serialized = $middleware->process($serialized);
        }

        return $serialized;
    }
}
