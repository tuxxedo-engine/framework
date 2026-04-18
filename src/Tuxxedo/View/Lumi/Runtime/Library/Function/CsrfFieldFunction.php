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

namespace Tuxxedo\View\Lumi\Runtime\Library\Function;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Security\Csrf\CsrfManagerInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeFrameInterface;

class CsrfFieldFunction implements FunctionInterface
{
    public private(set) string $name = 'csrf_field';
    public private(set) array $aliases = [];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /**
     * @param \Closure(): RuntimeFrameInterface $frame
     */
    public function call(
        array $arguments,
        \Closure $frame,
    ): string {
        $manager = $this->container->resolve(CsrfManagerInterface::class);

        return \sprintf(
            '<input type="hidden" name="%s" value="%s">',
            $manager->fieldName,
            \htmlspecialchars($manager->getToken(), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
        );
    }
}
