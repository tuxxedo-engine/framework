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
    public function __construct(
        public readonly MailTransportInterface $transport,
    ) {
    }

    public function send(
        MessageInterface ...$messages,
    ): void {
        $this->transport->send(...$messages);
    }
}
