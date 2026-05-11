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

namespace Support\Http\Request\Context;

use Tuxxedo\Http\HttpVersion;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\Context\ServerContextInterface;

class StubServerContext implements ServerContextInterface
{
    public bool $https = false;
    public HttpVersion $protocolVersion = HttpVersion::V1_1;
    public Method $method = Method::GET;
    public string $uri = '/';
    public string $fullUri = '/';
    public string $queryString = '';
    public string $userAgent = '';
    public string $host = 'localhost';
    public int $port = 80;
    public string $ipAddress = '127.0.0.1';
    public ?string $preferredLanguage = null;
    public array $preferredLanguages = [];
}
