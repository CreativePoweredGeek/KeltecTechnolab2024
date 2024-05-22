<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */
if (!function_exists('cartthrob')) {
    /**
     * Get the app container
     *
     * @param string|null $key
     * @return mixed
     */
    function cartthrob($key = null)
    {
        try {
            $container = \CartThrob\App::container();

            return $key ? $container->get($key) : $container;
        } catch (Exception $e) {
            show_exception($e);
        }
    }
}

/**
 * Load CartThrob to the package paths.
 * Confirm path isn't already loaded before loading to prevent duplicates in the package paths array.
 */
function loadCartThrobPath()
{
    if (!in_array(PATH_THIRD . 'cartthrob/', ee()->load->get_package_paths())) {
        ee()->load->add_package_path(PATH_THIRD . 'cartthrob/');
    }
}

/**
 * Unload CartTThrob from package paths.
 */
function unloadCartThrobPath()
{
    ee()->load->remove_package_path(PATH_THIRD . 'cartthrob/');
}

/**
 * @param $value
 * @return bool
 */
function isBase64Encoded($value)
{
    return base64_encode(base64_decode($value, true)) === $value;
}
