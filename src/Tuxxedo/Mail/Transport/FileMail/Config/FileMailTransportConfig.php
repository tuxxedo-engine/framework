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

namespace Tuxxedo\Mail\Transport\FileMail\Config;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Mail\Transport\FileMail\FileMailTransport;
use Tuxxedo\Mail\Transport\MailTransportInterface;

class FileMailTransportConfig implements FileMailTransportConfigInterface
{
    public function __construct(
        public readonly string $directory,
    ) {
    }

    public function createTransport(
        ContainerInterface $container,
    ): MailTransportInterface {
        return $container->resolve(FileMailTransport::class);
    }
}
