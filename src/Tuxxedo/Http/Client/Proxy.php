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

namespace Tuxxedo\Http\Client;

use Tuxxedo\Env\EnvException;
use Tuxxedo\Env\EnvInterface;
use Tuxxedo\Http\Url\Url;
use Tuxxedo\Http\Url\UrlException;
use Tuxxedo\Http\Url\UrlInterface;

readonly class Proxy implements ProxyInterface
{
    public UrlInterface $url;

    /**
     * @param list<string> $noProxy
     *
     * @throws UrlException
     */
    public function __construct(
        UrlInterface|string $url,
        public ?string $username = null,
        #[\SensitiveParameter] public ?string $password = null,
        public array $noProxy = [],
    ) {
        $this->url = $url instanceof UrlInterface
            ? $url
            : Url::parse($url);
    }

    /**
     * @throws EnvException
     * @throws UrlException
     */
    public static function fromEnv(
        EnvInterface $env,
        string $scheme = 'https',
    ): ?self {
        if ($scheme === 'http' && \PHP_SAPI !== 'cli') {
            return null; // @codeCoverageIgnore
        }

        $name = \strtolower($scheme) . '_proxy';
        $value = $env->string(\strtoupper($name), '');

        if ($value === '') {
            $value = $env->string($name, '');
        }

        if ($value === '') {
            return null;
        }

        $noProxy = $env->string('NO_PROXY', '');

        if ($noProxy === '') {
            $noProxy = $env->string('no_proxy', '');
        }

        return new self(
            url: \str_contains($value, '://')
                ? $value
                : 'http://' . $value,
            noProxy: \array_values(
                \array_filter(
                    \array_map(\trim(...), \explode(',', $noProxy)),
                    static fn (string $host): bool => $host !== '',
                ),
            ),
        );
    }
}
