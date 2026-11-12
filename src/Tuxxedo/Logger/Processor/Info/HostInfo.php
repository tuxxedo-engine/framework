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

namespace Tuxxedo\Logger\Processor\Info;

readonly class HostInfo implements HostInfoInterface
{
    public function __construct(
        public readonly ?string $name,
        public readonly OsInfoInterface $os,
        public readonly string $arch,
    ) {
    }
}
