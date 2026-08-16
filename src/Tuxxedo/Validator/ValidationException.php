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

namespace Tuxxedo\Validator;

use Tuxxedo\Http\Response\PrefersResponseCodeInterface;
use Tuxxedo\Http\Response\ResponseCode;

class ValidationException extends \Exception implements PrefersResponseCodeInterface
{
    public ?ResponseCode $responseCode {
        get {
            return ResponseCode::UNPROCESSABLE_ENTITY;
        }
    }

    /**
     * @param list<ViolationInterface> $violations
     */
    public function __construct(
        public readonly array $violations,
    ) {
        parent::__construct(
            message: 'Validation failed',
        );
    }
}
