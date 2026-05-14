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

namespace Tuxxedo\Http\Response\Stream;

interface SseEventInterface
{
    public ?string $data {
        get;
    }

    public ?string $id {
        get;
    }

    public ?string $event {
        get;
    }

    /**
     * @var positive-int|null
     */
    public ?int $retry {
        get;
    }

    public ?string $comment {
        get;
    }
}
