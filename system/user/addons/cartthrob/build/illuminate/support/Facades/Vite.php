<?php

namespace CartThrob\Dependency\Illuminate\Support\Facades;

/**
 * @method static string useCspNonce(?string $nonce = null)
 * @method static string|null cspNonce()
 * @method static \Illuminte\Foundation\Vite useIntegrityKey(string|false $key)
 * @method static \Illuminte\Foundation\Vite useScriptTagAttributes(callable|array $callback)
 * @method static \Illuminte\Foundation\Vite useStyleTagAttributes(callable|array $callback)
 *
 * @see \Illuminate\Foundation\Vite
 */
class Vite extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \CartThrob\Dependency\Illuminate\Foundation\Vite::class;
    }
}
