<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\Http\Request;

use Tuxxedo\Http\HeaderInterface;

interface RequestInterface
{
    public ServerContextInterface $context {
        get;
    }

    /**
     * @var HeaderContextInterface<array-key, HeaderInterface>
     */
    public HeaderContextInterface $headers {
        get;
    }

    /**
     * @var HeaderContextInterface<string, string>
     */
    public HeaderContextInterface $cookies {
        get;
    }

    // @todo Support GET
    // @todo Support POST
    // @todo Support Uploaded files
    // @todo Support body stream reading, e.g. Json
}
