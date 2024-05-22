<?php

namespace CartThrob\Dependency;

// Don't redefine the functions if included multiple times.
if (!\function_exists('CartThrob\\Dependency\\GuzzleHttp\\describe_type')) {
    require __DIR__ . '/functions.php';
}
