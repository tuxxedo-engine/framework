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

class UrlParamSource extends AbstractMemoizedLocaleSource
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly string $parameterName,
    ) {
    }

    protected function resolve(): ?Locale
    {
        if (!$this->request->get->has($this->parameterName)) {
            return null;
        }

        $value = $this->request->get->string($this->parameterName);

        try {
            return Locale::parse(
                input: $value,
            );
        } catch (TranslationException) {
            return null;
        }
    }
}
