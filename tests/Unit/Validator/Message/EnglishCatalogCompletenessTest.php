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

namespace Unit\Validator\Message;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Validator\Message\DefaultEnglishMessageFormatter;
use Tuxxedo\Validator\Violation;
use Tuxxedo\Validator\ViolationCodeInterface;

class EnglishCatalogCompletenessTest extends TestCase
{
    private const string RULE_ROOT = __DIR__ . '/../../../../src/Tuxxedo/Validator';

    public function testEveryShippedViolationCodeHasEnglishTemplate(): void
    {
        $formatter = new DefaultEnglishMessageFormatter();

        foreach (self::discoverViolationCodeEnums() as $enumClass) {
            $cases = $enumClass::cases();

            self::assertGreaterThan(
                0,
                \sizeof($cases),
                \sprintf(
                    'Enum "%s" declares no cases',
                    $enumClass,
                ),
            );

            foreach ($cases as $case) {
                /** @var ViolationCodeInterface&\BackedEnum $case */
                $formatted = $formatter->format(
                    violation: new Violation(
                        code: $case,
                        propertyPath: 'probe',
                        invalidValue: 'probe-value',
                    ),
                );

                self::assertStringNotContainsString(
                    'Validation failed at',
                    $formatted,
                    \sprintf(
                        'Missing English template for %s::%s ("%s")',
                        $enumClass,
                        $case->name,
                        (string) $case->value,
                    ),
                );
            }
        }
    }

    /**
     * @return list<class-string<ViolationCodeInterface&\BackedEnum>>
     */
    private static function discoverViolationCodeEnums(): array
    {
        $enums = [];

        /** @var \RecursiveIteratorIterator<\RecursiveDirectoryIterator> $iterator */
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                self::RULE_ROOT,
                \FilesystemIterator::SKIP_DOTS,
            ),
        );

        foreach ($iterator as $entry) {
            /** @var \SplFileInfo $entry */
            if (!$entry->isFile()) {
                continue;
            }

            if (!\str_ends_with($entry->getFilename(), 'ViolationCode.php')) {
                continue;
            }

            $className = self::classNameFromPath($entry->getPathname());

            if ($className === null) {
                continue;
            }

            if (!\enum_exists($className)) {
                continue;
            }

            $reflection = new \ReflectionEnum($className);

            if (!$reflection->implementsInterface(ViolationCodeInterface::class)) {
                continue;
            }

            if (!$reflection->isBacked()) {
                continue;
            }

            /** @var class-string<ViolationCodeInterface&\BackedEnum> $className */
            $enums[] = $className;
        }

        \sort($enums);

        return $enums;
    }

    /**
     * @return class-string|null
     */
    private static function classNameFromPath(
        string $path,
    ): ?string {
        $contents = \file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        if (\preg_match('/namespace\s+([^;]+);/', $contents, $namespaceMatch) !== 1) {
            return null;
        }

        if (\preg_match('/enum\s+([A-Za-z0-9_]+)/', $contents, $enumMatch) !== 1) {
            return null;
        }

        /** @var class-string $fqcn */
        $fqcn = \trim($namespaceMatch[1]) . '\\' . $enumMatch[1];

        return $fqcn;
    }
}
