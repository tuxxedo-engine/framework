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

namespace Tuxxedo\Http\Client;

use Tuxxedo\Http\Client\Request\ClientRequestInterface;

interface ClientProgressInterface
{
    public ClientRequestInterface $request {
        get;
    }

    public int $downloaded {
        get;
    }

    public ?int $downloadTotal {
        get;
    }

    public int $uploaded {
        get;
    }

    public ?int $uploadTotal {
        get;
    }
}
