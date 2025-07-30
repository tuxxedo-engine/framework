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

use Tuxxedo\Application\Profile;

return [
    /**
     * cli.name
     *
     * This can be used to identify a CLI application's name
     *
     * @type string
     * @required
     */
    'name' => 'Tuxxedo Engine CLI Demo',

    /**
     * cli.version
     *
     * This can be used to identify a CLI application's version
     *
     * @type string
     * @required
     */
    'version' => 'v0.1.0',

    /**
     * cli.profile
     *
     * This can be used to identify the profile a CLI application is in
     *
     * @type Profile
     * @required
     */
    'profile' => Profile::DEBUG,
];
