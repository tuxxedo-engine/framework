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

return [
    /**
     * database.manager.connections
     *
     * List of individual connections in their configuration structures
     *
     * @type array<mixed>
     */
    'connections' => [
        require __DIR__ . '/default.php',
    ],
];
