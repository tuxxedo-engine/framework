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

namespace Tuxxedo\View\Lumi\Library\Standard;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionProviderInterface;
use Tuxxedo\View\Lumi\Library\Function\PhpFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\ConfigFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\CsrfFieldFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\CsrfFieldNameFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\CsrfTokenFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\DateFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\DirectiveFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\DumpFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\HasDirectiveFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\IsEvenFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\IsOddFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\JoinFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\JsonFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\JsonPrettyFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\KSortFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\LeftPadFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\NowFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\NumberFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\PadFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\RepeatFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\ReplaceFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\RequestFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\ReverseFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\RightPadFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\RoundFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\RouteFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\SortFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\SplitFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\TruncateFunction;
use Tuxxedo\View\Lumi\Library\Standard\Function\UrlFunction;

class StandardFunctions implements FunctionProviderInterface
{
    public function export(
        ContainerInterface $container,
    ): \Generator {
        yield new HasDirectiveFunction();
        yield new DirectiveFunction();

        yield new ConfigFunction($container);
        yield new RequestFunction($container);
        yield new UrlFunction($container);
        yield new RouteFunction($container);
        yield new CsrfFieldFunction($container);
        yield new CsrfFieldNameFunction($container);
        yield new CsrfTokenFunction($container);

        yield new PhpFunction(
            name: 'count',
            aliases: [
                'sizeof',
            ],
        );

        yield new PhpFunction(
            name: 'constant',
            aliases: [
                'const',
            ],
        );

        yield new PhpFunction(
            name: 'base64',
            mappedName: 'base64_encode',
        );

        yield new RoundFunction();
        yield new NumberFunction();
        yield new IsOddFunction();
        yield new IsEvenFunction();

        yield new PhpFunction(
            name: 'abs',
        );

        yield new PhpFunction(
            name: 'ceil',
        );

        yield new PhpFunction(
            name: 'floor',
        );

        yield new PhpFunction(
            name: 'max',
        );

        yield new PhpFunction(
            name: 'min',
        );

        yield new PhpFunction(
            name: 'random',
            mappedName: 'random_int',
        );

        yield new PhpFunction(
            name: 'first',
            mappedName: 'array_first',
        );

        yield new PhpFunction(
            name: 'last',
            mappedName: 'array_last',
        );

        yield new PhpFunction(
            name: 'unique',
            mappedName: 'array_unique',
        );

        yield new PhpFunction(
            name: 'keys',
            mappedName: 'array_keys',
        );

        yield new PhpFunction(
            name: 'values',
            mappedName: 'array_values',
        );

        yield new SortFunction();
        yield new KSortFunction();

        yield new ReverseFunction();

        yield new JoinFunction();
        yield new SplitFunction();
        yield new ReplaceFunction();
        yield new RepeatFunction();
        yield new LeftPadFunction();
        yield new RightPadFunction();
        yield new PadFunction();
        yield new TruncateFunction();

        yield new DateFunction();
        yield new NowFunction();

        yield new DumpFunction();
        yield new JsonFunction();
        yield new JsonPrettyFunction();
    }
}
