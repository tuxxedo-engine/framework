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
     * app.name
     *
     * This can be used to identify an application's name
     *
     * @type string
     * @required
     */
    'name' => 'Tuxxedo Engine Demo',

    /**
     * app.version
     *
     * This can be used to identify an application's version
     *
     * @type string
     * @required
     */
    'version' => 'v0.1.0',

    /**
     * app.profile
     *
     * This can be used to identify the profile an application is in
     *
     * @type Profile
     * @required
     */
    'profile' => Profile::DEBUG,

    /**
     * app.url
     *
     * This can be used to set a base url for where an application is
     * deployed
     */
    'url' => 'http://localhost:13099/',
];
