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

namespace Tuxxedo\Mail\Serializer\Mime;

use Tuxxedo\Mail\HeaderInterface;

interface MimePartInterface
{
    public string $mimeType {
        get;
    }

    /**
     * @var list<HeaderInterface>
     */
    public array $headers {
        get;
    }
}
