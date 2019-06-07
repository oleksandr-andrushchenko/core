<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 11/8/17
 * Time: 9:38 PM
 */

namespace SNOWGIRL_CORE\Helper;

use Composer\Autoload\ClassLoader;
use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Helper\FS as FsHelper;

/**
 * Class Classes
 *
 * @package SNOWGIRL_CORE\Helper
 */
class Classes
{
    /**
     * @param ClassLoader $loader
     * @param $namespace
     * @return array
     */
    public static function getInNamespace(ClassLoader $loader, $namespace)
    {
        if (!$dir = self::getDirByNamespace($loader, $namespace)) {
            return [];
        }

        $files = FsHelper::globRecursive($dir . '/*.php');

        $classes = Arrays::mapByValueMaker($files, function ($file) use ($namespace, $dir) {
            return $namespace . '\\' . str_replace('/', '\\', str_replace($dir . '/', '', str_replace('.php', '', $file)));
        });

        return array_filter($classes, function ($class) {
            return class_exists($class);
        });
    }

    public static function getInNsCheckAppNs($ns, App $app)
    {
        $output = [];

        foreach ($app->namespaces as $alias => $namespace) {
            foreach (self::getInNamespace($app->loader, $namespace . '\\' . $ns) as $class) {
                $output[] = $class;
            }
        }

        return $output;
    }

    /**
     * @param ClassLoader $loader
     * @return array
     *
     * APP\ => /home/snowgirl/web/example.com/src
     * SNOWGIRL_SHOP\ => /home/snowgirl/web/example.com/vendor/snowgirl/shop/src
     * SNOWGIRL_CORE\ => /home/snowgirl/web/example.com/vendor/snowgirl/core/src
     * ...
     */
    public static function getPrefixToDirList(ClassLoader $loader)
    {
        return Arrays::mapByValueMaker($loader->getPrefixesPsr4(), function ($v) {
            return $v[0];
        });
    }

    public static function getDirByNamespace(ClassLoader $loader, $namespace)
    {
        $prefixToPath = self::getPrefixToDirList($loader);

        $namespaceFragments = explode('\\', $namespace);
        $undefinedNamespaceFragments = [];

        while ($namespaceFragments) {
            $possiblePrefix = implode('\\', $namespaceFragments) . '\\';

            if (array_key_exists($possiblePrefix, $prefixToPath)) {
                return realpath($prefixToPath[$possiblePrefix] . '/' . implode('/', $undefinedNamespaceFragments));
            }

            $undefinedNamespaceFragments[] = array_pop($namespaceFragments);
        }

        return false;
    }

    /**
     * @param App $app
     * @param $coreRelatedDir
     * @param $aliases
     * @param bool|false $withAliases
     * @param bool|true $whole
     * @return array
     */
    public static function getInDir(App $app, $coreRelatedDir, $aliases, $withAliases = false, $whole = true)
    {
        $output = [];

        $coreRelatedDir = trim($coreRelatedDir);

        foreach ($aliases as $alias) {
            $coreDir = $app->dirs[$alias] . '/src/';
            $dir = $coreDir . trim($coreRelatedDir) . '/';

            foreach (FsHelper::globRecursive($dir . '*.php') as $class) {
                $coreRelatedClass = str_replace($coreDir, '', $class);
                $coreRelatedClass = str_replace('.php', '', $coreRelatedClass);
                $coreRelatedClass = str_replace('/', '\\', $coreRelatedClass);

                $tmp = $alias . '\\' . str_replace(str_replace('/', '\\', $coreRelatedDir) . '\\', '', $coreRelatedClass);

                if ($withAliases) {
                    $output[$tmp] = $whole ? ($alias . '\\' . $coreRelatedClass) : $tmp;
                } else {
                    $output[$tmp] = $app->namespaces[$alias] . '\\' . $coreRelatedClass;
                }
            }
        }

        return $output;
    }

    public static function aliasToReal(App $app, $class, $coreRelatedNs = null)
    {
        return str_replace(
            array_keys($app->namespaces),
            $coreRelatedNs ? array_map(function ($ns) use ($coreRelatedNs) {
                return $ns . '\\' . $coreRelatedNs;
            }, $app->namespaces) : $app->namespaces,
            $class
        );
    }

    public static function getShortName($object)
    {
        return (new \ReflectionClass($object))->getShortName();
    }

    public static function isExists($className, App $app)
    {
        return $app->loader->findFile($className);
    }
}