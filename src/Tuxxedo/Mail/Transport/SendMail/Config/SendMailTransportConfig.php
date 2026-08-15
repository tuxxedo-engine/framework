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

namespace Tuxxedo\Mail\Transport\SendMail\Config;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Mail\Transport\MailTransportInterface;
use Tuxxedo\Mail\Transport\SendMail\SendMailTransport;

class SendMailTransportConfig implements SendMailTransportConfigInterface
{
    /**
     * @param list<string> $arguments
     */
    public function __construct(
        public readonly string $binary = '/usr/sbin/sendmail',
        public readonly array $arguments = [
            '-t',
            '-i',
        ],
        public readonly ?int $timeoutSeconds = 30,
    ) {
    }

    public function createTransport(
        ContainerInterface $container,
    ): MailTransportInterface {
        return $container->resolve(SendMailTransport::class);
    }
}
