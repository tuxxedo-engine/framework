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

namespace Tuxxedo\Model\Aggregate;

use Tuxxedo\Validator\ValidationException;
use Tuxxedo\Validator\ValidatorInterface;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationInterface;

class AggregateValidator
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @param list<CollectedEntity> $entities
     *
     * @throws ValidationException
     */
    public function validateOrThrow(
        array $entities,
        ?string $group = null,
    ): void {
        /** @var list<ViolationInterface> $aggregated */
        $aggregated = [];

        foreach ($entities as $collected) {
            try {
                $this->validator->validateOrThrow(
                    target: $collected->entity,
                    group: $group,
                );
            } catch (ValidationException $exception) {
                foreach ($exception->violations as $violation) {
                    $aggregated[] = new Violation(
                        code: $violation->code,
                        propertyPath: $this->prefixPath(
                            entityPath: $collected->path,
                            violationPath: $violation->propertyPath,
                        ),
                        invalidValue: $violation->invalidValue,
                        context: $violation->context,
                    );
                }
            }
        }

        if (\sizeof($aggregated) === 0) {
            return;
        }

        throw new ValidationException(
            violations: $aggregated,
        );
    }

    private function prefixPath(
        string $entityPath,
        string $violationPath,
    ): string {
        if ($entityPath === '') {
            return $violationPath;
        }

        if ($violationPath === '') {
            return $entityPath; // @codeCoverageIgnore
        }

        return $entityPath . '.' . $violationPath;
    }
}
