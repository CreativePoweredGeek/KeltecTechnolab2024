<?php

namespace CartThrob;

use CartThrob\Dependency\DI\Container;
use CartThrob\Dependency\DI\ContainerBuilder;

class App
{
    /** @var Container */
    private static $container;

    public static function container()
    {
        if (!static::$container) {
            $containerBuilder = (new ContainerBuilder())
                ->addDefinitions([
                    \EE_Session::class => function () {
                        return ee()->session;
                    },
                    \ExpressionEngine\Service\Encrypt\Encrypt::class => function () {
                        return ee('Encrypt');
                    },
                ])
                ->useAutowiring(true);

//            $containerBuilder->enableCompilation(SYSPATH . 'user/cache/cartthrob');

            static::$container = $containerBuilder->build();
        }

        return static::$container;
    }
}
