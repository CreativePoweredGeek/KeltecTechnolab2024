<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitbc9b9e2db4e9cc7c010d55bb1c9202ca
{
    public static $prefixLengthsPsr4 = array (
        'Z' => 
        array (
            'Zenbu\\' => 6,
        ),
        'T' => 
        array (
            'Twig\\' => 5,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Zenbu\\' => 
        array (
            0 => __DIR__ . '/../..' . '/',
        ),
        'Twig\\' => 
        array (
            0 => __DIR__ . '/..' . '/twig/twig/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'T' => 
        array (
            'Twig_' => 
            array (
                0 => __DIR__ . '/..' . '/twig/twig/lib',
            ),
        ),
    );

    public static $classMap = array (
        'Mexitek\\PHPColors\\Color' => __DIR__ . '/..' . '/mexitek/phpcolors/src/Mexitek/PHPColors/Color.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitbc9b9e2db4e9cc7c010d55bb1c9202ca::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitbc9b9e2db4e9cc7c010d55bb1c9202ca::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInitbc9b9e2db4e9cc7c010d55bb1c9202ca::$prefixesPsr0;
            $loader->classMap = ComposerStaticInitbc9b9e2db4e9cc7c010d55bb1c9202ca::$classMap;

        }, null, ClassLoader::class);
    }
}
