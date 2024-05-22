<?php

namespace CartThrob\Dependency\Illuminate\Contracts\Container;

use Exception;
use CartThrob\Dependency\Psr\Container\ContainerExceptionInterface;
class CircularDependencyException extends Exception implements ContainerExceptionInterface
{
    //
}
