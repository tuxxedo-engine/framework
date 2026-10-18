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

use App\Input\TimezonePickerInput;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\Validator\Message\MessageFormatterInterface;
use Tuxxedo\Validator\ValidatorInterface;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewInterface;

#[Controller(path: '/timezones')]
readonly class TimezoneController
{
    public function __construct(
        private ValidatorInterface $validator,
        private MessageFormatterInterface $messageFormatter,
    ) {
    }

    #[Route\Get(path: '/')]
    public function index(
        RequestInterface $request,
    ): ViewInterface {
        $country = \strtoupper($request->get->string('country'));
        $input = new TimezonePickerInput(
            locale: $request->get->string('locale', TimezonePickerInput::LOCALES[0]),
            country: $country !== ''
                ? $country
                : null,
        );

        $result = $this->validator->validate($input);
        $errors = [];

        foreach ($result->violations as $violation) {
            $errors[] = $this->messageFormatter->format($violation);
        }

        return new View(
            name: 'timezones',
            scope: [
                'pickerLocales' => TimezonePickerInput::LOCALES,
                'pickerLocale' => $result->isValid
                    ? $input->locale
                    : TimezonePickerInput::LOCALES[0],
                'countryInput' => $country,
                'country' => $result->isValid
                    ? $input->country
                    : null,
                'errors' => $errors,
            ],
        );
    }
}
