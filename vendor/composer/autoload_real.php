<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit1660623dc5935e4e4d0ae461c4e069a3
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInit1660623dc5935e4e4d0ae461c4e069a3', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit1660623dc5935e4e4d0ae461c4e069a3', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit1660623dc5935e4e4d0ae461c4e069a3::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
