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

class ServerContext implements ServerContextInterface
{
    public bool $https {
        get {
            return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        }
    }
}
