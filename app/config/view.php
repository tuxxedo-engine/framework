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
     * view.directory
     *
     * Directory path for Tuxxedo Engine Lumi views
     *
     * @type string
     */
    'directory' => __DIR__ . '/../views',

    /**
     * view.cacheDirectory
     *
     * Cache directory path for compiled Tuxxedo Engine Lumi views
     *
     * @type string
     */
    'cacheDirectory' => __DIR__ . '/../views/cache',

    /**
     * view.extension
     *
     * File extension for Tuxxedo Engine Lumi views
     *
     * @type string
     */
    'extension' => '.lumi',

    /**
     * view.alwaysCompile
     *
     * Whether to always compile Tuxxedo Engine Lumi views
     *
     * @type bool
     */
    'alwaysCompile' => true,

    /**
     * view.disableErrorReporting
     *
     * Whether to disable PHP's error reporting while rendering Tuxxedo Engine Lumi views
     *
     * @type bool
     */
    'disableErrorReporting' => true,
];
