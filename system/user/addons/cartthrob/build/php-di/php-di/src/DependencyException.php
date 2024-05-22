<?php

declare (strict_types=1);
namespace CartThrob\Dependency\DI;

use CartThrob\Dependency\Psr\Container\ContainerExceptionInterface;
/**
 * Exception for the Container.
 */
class DependencyException extends \Exception implements ContainerExceptionInterface
{
}
