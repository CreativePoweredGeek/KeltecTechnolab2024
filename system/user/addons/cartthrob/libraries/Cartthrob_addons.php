<?php

use CartThrob\Dependency\Illuminate\Support\Arr;
use CartThrob\Dependency\Illuminate\Support\Str;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * CartThrob Addons
 */
class Cartthrob_addons
{
    /**
     * @var array container for addon module/plugin instances; class => object
     */
    private $addons = [];

    /**
     * @var array the list of available methods; method => class
     */
    private $methods = [];

    /**
     * @var object the last module/plugin instance checked in method_exists
     */
    private $cached_addon;

    public function __construct()
    {
        $validAddons = [];
        foreach ($validAddons as $shortName) {
            if (str_contains($shortName, 'cartthrob_')) {
                $shortName = str_replace('cartthrob_', '', $shortName);
            }

            $paths = [
                PATH_THIRD . 'cartthrob_' . $shortName . '/mod.cartthrob_' . $shortName . '.php',
                PATH_THIRD . 'cartthrob_' . $shortName . '/pi.cartthrob_' . $shortName . '.php',
            ];

            foreach ($paths as $path) {
                if (@file_exists($path)) {
                    require_once $path;

                    $this->register('Cartthrob_' . $shortName);

                    break;
                }
            }
        }
    }

    /**
     * register a module/plugin so that you can use it's tags with cartthrob
     *
     * @param string|object $class the classname of the module/plugin
     */
    public function register($class)
    {
        if (is_object($class)) {
            $object = $class;

            $class = get_class($object);
        } else {
            $object = new $class();
        }

        $this->addons[$class] = $object;

        foreach (get_class_methods($class) as $method) {
            // "private" or magic method, skip
            if (strncmp($method, '_', 1) === 0) {
                continue;
            }

            $this->methods[$method] = $class;
        }

        $this->registerActions($class);
        $this->registerTags($class);
    }

    /**
     * call a cartthrob addon's method from the cartthrob module
     * pass args via TMPL class
     *
     * @param string $method name of the template tag method
     *
     * @return mixed
     */
    public function call($method)
    {
        if (is_null($this->cached_addon)) {
            if (!$this->method_exists($method)) {
                return;
            }
        }

        $result = $this->cached_addon->$method();

        $this->cached_addon = null;

        return $result;
    }

    /**
     * @param $methodName
     * @return bool
     */
    public function method_exists($methodName)
    {
        if (!$className = $this->getClassFromMethodName($methodName)) {
            return false;
        }

        if (!$addon = $this->getAddonFromClassName($className)) {
            return false;
        }

        $this->cached_addon = &$addon;

        return true;
    }

    private function getClassFromMethodName($method)
    {
        return Arr::get($this->methods, $method);
    }

    private function getAddonFromClassName($class)
    {
        return Arr::get($this->addons, $class);
    }

    private function registerTags($class)
    {
        if (class_exists($class)) {
            $reflection = new ReflectionClass($class);

            $tagsPath = dirname($reflection->getFileName()) . '/Tags';

            if (is_dir($tagsPath)) {
                $tags = new FilesystemIterator($tagsPath, FilesystemIterator::UNIX_PATHS | FilesystemIterator::SKIP_DOTS);

                foreach ($tags as $tag) {
                    $method = str_replace('_tag.php', '', Str::snake($tag->getFilename()));

                    $this->methods[$method] = $class;
                }
            }
        }
    }

    private function registerActions($class)
    {
        if (class_exists($class)) {
            $reflection = new ReflectionClass($class);

            $tagsPath = dirname($reflection->getFileName()) . '/Actions';

            if (is_dir($tagsPath)) {
                $tags = new FilesystemIterator($tagsPath, FilesystemIterator::UNIX_PATHS | FilesystemIterator::SKIP_DOTS);

                foreach ($tags as $tag) {
                    $method = str_replace('_action.php', '', Str::snake($tag->getFilename()));

                    $this->methods[$method] = $class;
                }
            }
        }
    }
}
