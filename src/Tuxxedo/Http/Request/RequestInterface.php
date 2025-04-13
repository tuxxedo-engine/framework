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

use Tuxxedo\Http\Method;

interface RequestInterface
{
    public function getServerEnvironment(): ServerEnvironmentInterface;

    public function getRequestMethod(): Method;
    public function getRequestUri(): string;
    public function isHttps(): bool;

    public function getRequestTime(): int;
    public function getRequestTimeFloat(): float;
}
