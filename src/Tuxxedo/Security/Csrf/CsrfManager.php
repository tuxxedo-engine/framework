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

namespace Tuxxedo\Security\Csrf;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DefaultInitializer;
use Tuxxedo\Security\Csrf\Storage\CsrfSessionStorageHandler;
use Tuxxedo\Security\Csrf\Storage\CsrfStorageHandlerInterface;

#[DefaultInitializer(
    static function (ContainerInterface $container): CsrfManagerInterface {
        return new CsrfManager(
            storage: $container->resolve(CsrfSessionStorageHandler::class),
        );
    },
)]
class CsrfManager implements CsrfManagerInterface
{
    public function __construct(
        private readonly CsrfStorageHandlerInterface $storage,
        public readonly string $fieldName = '__csrf_token',
    ) {
    }

    public function getToken(): string
    {
        $token = $this->storage->get();

        if ($token === null) {
            $token = $this->regenerate();
        }

        return $token;
    }

    public function regenerate(): string
    {
        $token = \bin2hex(\random_bytes(32));

        $this->storage->set($token);

        return $token;
    }

    public function validate(
        string $token,
    ): bool {
        $stored = $this->storage->get();

        if ($stored === null) {
            return false;
        }

        return \hash_equals($stored, $token);
    }

    public function clear(): void
    {
        $this->storage->clear();
    }
}
