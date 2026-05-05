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
use Tuxxedo\View\Lumi\Library\Filter\FilterProviderInterface;
use Tuxxedo\View\Lumi\Library\Standard\Filter\CapitalizeFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\CountFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\DumpFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\EscapeAttrFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\EscapeCssFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\EscapeHtmlCommentFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\EscapeHtmlFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\EscapeJsFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\JsonFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\JsonPrettyFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\LcfirstFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\LengthFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\LowerFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\LtrimFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\Nl2brFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\RtrimFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\SlugifyFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\StripTagsFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\TrimFilter;
use Tuxxedo\View\Lumi\Library\Standard\Filter\UpperFilter;

readonly class StandardFilters implements FilterProviderInterface
{
    public function export(
        ContainerInterface $container,
    ): \Generator {
        yield $container->resolve(EscapeAttrFilter::class);
        yield $container->resolve(EscapeCssFilter::class);
        yield $container->resolve(EscapeHtmlFilter::class);
        yield $container->resolve(EscapeHtmlCommentFilter::class);
        yield $container->resolve(EscapeJsFilter::class);

        yield new CountFilter();
        yield new LengthFilter();

        yield new LtrimFilter();
        yield new RtrimFilter();
        yield new TrimFilter();

        yield new JsonFilter();
        yield new JsonPrettyFilter();

        yield new LowerFilter();
        yield new UpperFilter();
        yield new CapitalizeFilter();
        yield new LcfirstFilter();
        yield new SlugifyFilter();

        yield new Nl2brFilter();
        yield new StripTagsFilter();
        yield new DumpFilter();
    }
}
