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

namespace Tuxxedo\View\Lumi\Runtime\Library;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionProviderInterface;
use Tuxxedo\View\Lumi\Library\Function\PhpFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\ConfigFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\CsrfFieldFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\CsrfFieldNameFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\CsrfTokenFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\DateFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\DirectiveFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\DumpFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\HasDirectiveFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\IsEvenFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\IsOddFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\JoinFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\JsonFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\JsonPrettyFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\KSortFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\LeftPadFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\NowFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\NumberFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\PadFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\RepeatFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\ReplaceFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\RequestFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\ReverseFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\RightPadFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\RoundFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\RouteFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\SortFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\SplitFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\TruncateFunction;
use Tuxxedo\View\Lumi\Runtime\Library\Function\UrlFunction;

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
            name: 'hash',
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
