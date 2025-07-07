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

use Tuxxedo\Http\SameSite;

return [
    /**
     * session.lifetime
     *
     * Lifetime of a session in seconds (Required by PhpSessionAdapter)
     *
     * @type int
     */
    'lifetime' => 3600,

    /**
     * session.path
     *
     * URI path for session availability for session cookies (Required by PhpSessionAdapter)
     *
     * @type string
     */
    'path' => '/',

    /**
     * session.domain
     *
     * Domain where the above URI exists for session availability for session cookies (Required by PhpSessionAdapter)
     *
     * @type string
     */
    'domain' => '',

    /**
     * session.httpOnly
     *
     * HttpOnly security property for session cookies (Required by PhpSessionAdapter)
     *
     * @type bool
     */
    'httpOnly' => true,

    /**
     * session.secure
     *
     * Whether HTTPS is required for the session (Required by PhpSessionAdapter)
     *
     * @type bool
     */
    'secure' => false,

    /**
     * session.sameSite
     *
     * SameSite security property for session cookies (Required by PhpSessionAdapter)
     *
     * @type SameSite
     */
    'sameSite' => SameSite::STRICT,
];
