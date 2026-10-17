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

namespace App\Controllers;

use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewInterface;

#[Controller(path: '/timezones')]
readonly class TimezoneController
{
    private const array PICKER_LOCALES = [
        'en-US',
        'da-DK',
        'pt-BR',
        'cs-CZ',
    ];

    #[Route\Get(path: '/')]
    public function index(
        RequestInterface $request,
    ): ViewInterface {
        $locale = $request->get->string('locale');
        $country = \strtoupper($request->get->string('country'));

        return new View(
            name: 'timezones',
            scope: [
                'pickerLocales' => self::PICKER_LOCALES,
                'pickerLocale' => \in_array($locale, self::PICKER_LOCALES, true)
                    ? $locale
                    : self::PICKER_LOCALES[0],
                'countryInput' => $country,
                'country' => \preg_match('/^[A-Z]{2}$/', $country) === 1
                    ? $country
                    : null,
            ],
        );
    }
}
