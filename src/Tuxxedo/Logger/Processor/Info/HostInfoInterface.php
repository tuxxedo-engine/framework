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

interface HostInfoInterface
{
    public ?string $name {
        get;
    }

    public OsInfoInterface $os {
        get;
    }

    public string $arch {
        get;
    }
}
