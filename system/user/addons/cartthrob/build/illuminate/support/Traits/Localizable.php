<?php

namespace CartThrob\Dependency\Illuminate\Support\Traits;

use CartThrob\Dependency\Illuminate\Container\Container;
trait Localizable
{
    /**
     * Run the callback with the given locale.
     *
     * @param  string  $locale
     * @param  \Closure  $callback
     * @return mixed
     */
    public function withLocale($locale, $callback)
    {
        if (!$locale) {
            return $callback();
        }
        $app = Container::getInstance();
        $original = $app->getLocale();
        try {
            $app->setLocale($locale);
            return $callback();
        } finally {
            $app->setLocale($original);
        }
    }
}
