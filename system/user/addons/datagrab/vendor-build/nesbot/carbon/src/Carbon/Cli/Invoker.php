<?php

namespace BoldMinded\DataGrab\Dependency\Carbon\Cli;

class Invoker
{
    public const CLI_CLASS_NAME = 'BoldMinded\\DataGrab\\Dependency\\Carbon\\Cli';
    protected function runWithCli(string $className, array $parameters) : bool
    {
        $cli = new $className();
        return $cli(...$parameters);
    }
    public function __invoke(...$parameters) : bool
    {
        if (\class_exists(self::CLI_CLASS_NAME)) {
            return $this->runWithCli(self::CLI_CLASS_NAME, $parameters);
        }
        $function = (($parameters[1] ?? '') === 'install' ? $parameters[2] ?? null : null) ?: 'shell_exec';
        $function('composer require carbon-cli/carbon-cli --no-interaction');
        echo 'Installation succeeded.';
        return \true;
    }
}
