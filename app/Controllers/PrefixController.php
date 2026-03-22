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

namespace App\Controllers;

use App\Support\MandatoryLanguagePrefix;
use App\Support\OptionalLanguagePrefix;
use Tuxxedo\Router\Attribute\Argument;
use Tuxxedo\Router\Attribute\Controller;
use Tuxxedo\Router\Attribute\Index;
use Tuxxedo\Router\Attribute\Route;
use Tuxxedo\View\View;
use Tuxxedo\View\ViewInterface;

#[Controller(
    uri: '/language/',
    prefix: OptionalLanguagePrefix::class,
)]
readonly class PrefixController
{
    #[Route\Get(name: 'lang.mandatory', prefix: MandatoryLanguagePrefix::class)]
    public function mandatory(
        #[Argument] string $language,
    ): ViewInterface {
        return new View(
            name: 'support/language',
            scope: [
                'language' => $language,
                'optional' => false,
            ],
        );
    }

    #[Index]
    #[Route\Get(name: 'lang.optional')]
    public function optional(
        #[Argument] string $language = 'en',
    ): ViewInterface {
        return new View(
            name: 'support/language',
            scope: [
                'language' => $language,
                'optional' => true,
            ],
        );
    }
}
