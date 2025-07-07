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

namespace Tuxxedo\Session\Adapters;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Http\SameSite;
use Tuxxedo\Session\SessionAdapterInterface;
use Tuxxedo\Session\SessionException;
use Tuxxedo\Session\SessionStartMode;

class PhpSessionAdapter implements SessionAdapterInterface
{
    private bool $started = false;

    final public function __construct(
        public readonly SessionStartMode $startMode,
        private readonly int $lifetime = 3600,
        private readonly string $path = '/',
        private readonly string $domain = '',
        private readonly bool $httpOnly = true,
        private readonly bool $secure = false,
        private readonly SameSite $sameSite = SameSite::STRICT,
    ) {
        if ($this->startMode === SessionStartMode::AUTO) {
            $this->start();
        }
    }

    public static function createFromConfig(
        SessionStartMode $startMode,
        ConfigInterface $config,
    ): static {
        return new static(
            startMode: $startMode,
            lifetime: $config->getInt('session.lifetime'),
            path: $config->getString('session.path'),
            domain: $config->getString('session.domain'),
            httpOnly: $config->getBool('session.httpOnly'),
            secure: $config->getBool('session.secure'),
            sameSite: $config->getEnum('session.sameSite', SameSite::class),
        );
    }

    /**
     * @throws SessionException
     */
    private function startCheck(): void
    {
        if (!$this->started && $this->startMode === SessionStartMode::LAZY) {
            $this->start();
        } else {
            throw SessionException::fromNotStarted();
        }
    }

    /**
     * @return array{
     *     lifetime: int,
     *     path: string,
     *     domain: string,
     *     httpOnly: bool,
     *     secure: bool,
     *     sameSite: 'Lax'|'Strict',
     *  }
     */
    private function getCookieOptions(): array
    {
        return [
            'lifetime' => $this->lifetime,
            'path' => $this->path,
            'domain' => $this->domain,
            'httpOnly' => $this->httpOnly,
            'secure' => $this->secure,
            'sameSite' => $this->sameSite->value,
        ];
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function start(): static
    {
        if (!$this->started) {
            \session_set_cookie_params($this->getCookieOptions()) or throw SessionException::fromCannotStart();
            \session_start() or throw SessionException::fromCannotStart();

            $this->started = true;
        }

        return $this;
    }

    public function stop(): static
    {
        if ($this->started) {
            \session_destroy() or throw SessionException::fromCannotStop();

            $this->started = false;
        }

        return $this;
    }

    public function restart(): static
    {
        return $this->stop()->start();
    }

    public function clear(): static
    {
        $this->startCheck();

        \session_unset();

        return $this;
    }

    public function getIdentifier(): string
    {
        $this->startCheck();

        $identifier = \session_id();

        if ($identifier === false) {
            throw SessionException::fromCannotFetchIdentifier();
        }

        return $identifier;
    }

    public function regenerateIdentifier(): static
    {
        $this->startCheck();

        if (!\session_regenerate_id()) {
            throw SessionException::fromCannotRegenerateIdentifier();
        }

        return $this;
    }

    public function has(
        string $name,
    ): bool {
        $this->startCheck();

        return \array_key_exists($name, $_SESSION);
    }

    public function set(
        string $name,
        mixed $value,
    ): static {
        $this->startCheck();

        if ($value instanceof \UnitEnum) {
            $value = $value->name;
        }

        $_SESSION[$name] = $value;

        return $this;
    }

    public function getRaw(
        string $name,
        mixed $default = null,
    ): mixed {
        $this->startCheck();

        if (!\array_key_exists($name, $_SESSION)) {
            return $default;
        }

        return $_SESSION[$name];
    }
}
