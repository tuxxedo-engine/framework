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

namespace Integration\Model;

use Fixture\Model\ValidatedProfile;
use Tuxxedo\Validator\Rule\Length\LengthViolationCode;
use Tuxxedo\Validator\ValidationException;

abstract class AbstractValidationIntegrationTestCase extends AbstractModelIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->modelsManager->createTable(
            modelClass: ValidatedProfile::class,
        )->execute();
    }

    public function testSaveValidModelSucceeds(): void
    {
        $profile = new ValidatedProfile();
        $profile->handle = 'alice';

        $saved = $this->modelsManager->save(
            model: $profile,
        );

        self::assertNotNull($saved->id);
        self::assertSame('alice', $saved->handle);
    }

    public function testSaveInvalidModelThrowsValidationException(): void
    {
        $profile = new ValidatedProfile();
        $profile->handle = 'this-value-is-way-too-long-for-varchar-10';

        try {
            (void) $this->modelsManager->save(
                model: $profile,
            );

            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(1, \sizeof($e->violations));
            self::assertSame(
                LengthViolationCode::TOO_LONG,
                $e->violations[0]->code,
            );
        }
    }

    public function testSkipValidationBypassesValidator(): void
    {
        $profile = new ValidatedProfile();
        $profile->handle = 'short';

        $saved = $this->modelsManager->save(
            model: $profile,
            skipValidation: true,
        );

        self::assertNotNull($saved->id);
    }

    public function testSkipValidationDoesNotThrowValidationExceptionForInvalidData(): void
    {
        $profile = new ValidatedProfile();
        $profile->handle = 'short';

        try {
            $saved = $this->modelsManager->save(
                model: $profile,
                skipValidation: true,
            );
        } catch (ValidationException $e) {
            self::fail('Expected ValidationException to be skipped, got: ' . $e->getMessage());
        }

        self::assertSame('short', $saved->handle);
    }
}
