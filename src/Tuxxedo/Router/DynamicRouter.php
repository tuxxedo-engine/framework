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

namespace Tuxxedo\Router;

use Tuxxedo\Container\ContainerInterface;

class DynamicRouter extends StaticRouter
{
    public function __construct(
        ContainerInterface $container,
        string $directory,
        string $baseNamespace,
    ) {
        // @todo Change this so we do not need to use \iterator_to_array() for performance? Unless it will be volatile
        //       as we cannot use route priorities
        parent::__construct(
            routes: \iterator_to_array(
                iterator: (new RouteDiscoverer(
                    container: $container,
                    baseNamespace: $baseNamespace,
                    directory: $directory,
                ))->discover(),
                preserve_keys: false,
            ),
        );
    }
}
