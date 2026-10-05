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

namespace Tuxxedo\View\Lumi\Library\Standard\Function;

use Tuxxedo\Security\Csrf\CsrfManagerInterface;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;

class CsrfFunctions
{
    public function __construct(
        private readonly CsrfManagerInterface $manager,
    ) {
    }

    #[LumiFunction('csrf_field')]
    public function csrfField(): string
    {
        return \sprintf(
            '<input type="hidden" name="%s" value="%s">',
            $this->manager->fieldName,
            \htmlspecialchars($this->manager->getToken(), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
        );
    }

    #[LumiFunction('csrf_field_name')]
    public function csrfFieldName(): string
    {
        return $this->manager->fieldName;
    }

    #[LumiFunction('csrf_token')]
    public function csrfToken(): string
    {
        return $this->manager->getToken();
    }
}
