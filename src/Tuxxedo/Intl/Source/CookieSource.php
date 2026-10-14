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

namespace Tuxxedo\Intl\Source;

use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\TranslationException;

class CookieSource extends AbstractMemoizedLocaleSource
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly string $cookieName,
    ) {
    }

    protected function resolve(): ?Locale
    {
        if (!$this->request->cookies->has($this->cookieName)) {
            return null;
        }

        $value = $this->request->cookies->string($this->cookieName);

        try {
            return Locale::parse(
                input: $value,
            );
        } catch (TranslationException) {
            return null;
        }
    }
}
