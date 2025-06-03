<?php

declare(strict_types=1);

namespace
{
    use Tuxxedo\Application\ApplicationFactory;
    use Tuxxedo\Http\Request\RequestFactory;
    use Tuxxedo\Http\Request\RequestInterface;

    require_once __DIR__ . '/../vendor/autoload.php';

    interface A
    {
        public function foo(): string;
    }

    class B implements A
    {
        public function foo(): string
        {
            return __METHOD__;
        }
    }

    class C
    {
        public function foo(): string
        {
            return __METHOD__;
        }
    }

    $app = ApplicationFactory::createFromDirectory(
        directory: __DIR__ . '/../app',
    );

    $app->container->persistent(RequestFactory::createFromEnvironment());

    echo '<pre>';
    var_dump(
        $app->container->resolve(RequestInterface::class)->context->https,
    );
    echo '</pre>';
}
