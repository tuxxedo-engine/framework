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

namespace Tuxxedo\Http\Client\Response;

use Tuxxedo\Http\CookieInterface;
use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Request\Context\BodyContextInterface;
use Tuxxedo\Http\Request\Context\HeaderContextInterface;
use Tuxxedo\Http\Response\ResponsableInterface;

interface ClientResponseInterface extends ResponsableInterface
{
    public bool $buffered {
        get;
    }

    public int $statusCode {
        get;
    }

    public HttpVersion $httpVersion {
        get;
    }

    public HeaderContextInterface $headers {
        get;
    }

    public BodyContextInterface $body {
        get;
    }

    /**
     * @var CookieInterface[]
     */
    public array $cookies {
        get;
    }

    public ClientResponseInfoInterface $info {
        get;
    }
}
