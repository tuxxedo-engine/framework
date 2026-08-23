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

namespace Tuxxedo\Router\Compiler;

use Tuxxedo\Router\ArgumentKind;
use Tuxxedo\Router\ArgumentNode;
use Tuxxedo\Router\Pattern\TypePatternRegistryInterface;
use Tuxxedo\Router\PrefixInterface;

class PathCompiler implements PathCompilerInterface
{
    private const string ARGUMENT_REGEX = '/(\/?)\{(\??)([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+)|<([^>]+)>)?}/';

    public function __construct(
        private readonly TypePatternRegistryInterface $patterns,
    ) {
    }

    public function compile(
        string $path,
        ?PrefixInterface $prefix = null,
    ): CompiledPathInterface {
        $prefixedArguments = [];

        if ($prefix !== null) {
            $prefixMatchCount = \preg_match_all(
                self::ARGUMENT_REGEX,
                $prefix->path,
                $prefixMatches,
                \PREG_SET_ORDER,
            );

            if ($prefixMatchCount !== false && $prefixMatchCount > 0) {
                foreach ($prefixMatches as $prefixMatch) {
                    $prefixedArguments[] = $prefixMatch[3];
                }
            }
        }

        $nodes = [];
        $patterns = $this->patterns;

        $regexBody = \preg_replace_callback(
            self::ARGUMENT_REGEX,
            static function (array $matches) use (&$nodes, $prefixedArguments, $patterns): string {
                $slash = $matches[1];
                $optional = $matches[2] === '?';
                $name = $matches[3];
                $regexConstraint = ($matches[4] ?? '') !== ''
                    ? $matches[4]
                    : null;
                $typeConstraint = ($matches[5] ?? '') !== ''
                    ? $matches[5]
                    : null;

                $kind = ArgumentKind::TYPED_IMPLICIT;
                $constraint = null;

                if ($typeConstraint !== null) {
                    $kind = ArgumentKind::TYPED_EXPLICIT;
                    $constraint = $typeConstraint;
                } elseif ($regexConstraint !== null) {
                    $kind = ArgumentKind::REGEX;
                    $constraint = $regexConstraint;
                }

                $nodes[] = new ArgumentNode(
                    name: $name,
                    kind: $kind,
                    constraint: $constraint,
                    optional: $optional,
                    prefixed: \in_array($name, $prefixedArguments, true),
                );

                $pattern = $regexConstraint
                    ?? $patterns->get($typeConstraint ?? '')->regex
                    ?? '[^/]+';

                $segment = '(?<' . $name . '>' . $pattern . ')';

                return $optional
                    ? '(?:' . $slash . $segment . ')?'
                    : $slash . $segment;
            },
            $path,
        ) ?? $path;

        return new CompiledPath(
            regexPath: '#^' . $regexBody . '$#',
            argumentNodes: $nodes,
        );
    }
}
