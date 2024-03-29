<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit082961c384f8032aa6835d443214e1f5
{
    public static $files = array (
        '603ce470d3b0980801c7a6185a3d6d53' => __DIR__ . '/..' . '/icanboogie/inflector/lib/helpers.php',
    );

    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
        'I' => 
        array (
            'ICanBoogie\\' => 11,
        ),
        'D' => 
        array (
            'Doctrine\\Inflector\\' => 19,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
        'ICanBoogie\\' => 
        array (
            0 => __DIR__ . '/..' . '/icanboogie/inflector/lib',
        ),
        'Doctrine\\Inflector\\' => 
        array (
            0 => __DIR__ . '/..' . '/doctrine/inflector/lib/Doctrine/Inflector',
        ),
    );

    public static $prefixesPsr0 = array (
        'O' => 
        array (
            'OAuth2' => 
            array (
                0 => __DIR__ . '/..' . '/bshaffer/oauth2-server-php/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit082961c384f8032aa6835d443214e1f5::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit082961c384f8032aa6835d443214e1f5::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit082961c384f8032aa6835d443214e1f5::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit082961c384f8032aa6835d443214e1f5::$classMap;

        }, null, ClassLoader::class);
    }
}
