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

namespace App\Service\Lazy;

use App\Service\Logger\CustomLoggerInterface;

class LazyService implements LazyServiceInterface
{
    public function __construct(
        public readonly CustomLoggerInterface $logger,
    ) {
        $this->logger->info(
            message: 'Created {class}',
            placeholders: [
                'class' => $this::class,
            ],
        );
    }
}
