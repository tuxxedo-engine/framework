<?php

declare(strict_types=1);

namespace
{
    use Tuxxedo\Application\Application;
    use Tuxxedo\Application\ApplicationFactory;
    use Tuxxedo\Application\ApplicationState;
    use Tuxxedo\Config\ConfigInterface;
    use Tuxxedo\Container\Container;
    use Tuxxedo\Container\Resolvers\App;
    use Tuxxedo\Container\Resolvers\AppName;
    use Tuxxedo\Container\Resolvers\AppVersion;
    use Tuxxedo\Container\Resolvers\AppState;
    use Tuxxedo\Container\Resolvers\AppContainer;
    use Tuxxedo\Container\Resolvers\ConfigValue;
    use Tuxxedo\Http\ResponseCode;

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

    echo '<pre>';
    var_dump(
        $app->container->persistent(B::class)->resolve(A::class)->foo(),
        $app->container->persistent(C::class)->resolve(A::class)->foo(),
        $app->container->persistent(C::class)->alias(A::class, C::class)->resolve(A::class)->foo(),
    );
    echo '</pre>';
}
