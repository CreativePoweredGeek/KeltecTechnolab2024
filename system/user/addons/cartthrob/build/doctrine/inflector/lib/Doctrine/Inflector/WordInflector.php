<?php

declare (strict_types=1);
namespace CartThrob\Dependency\Doctrine\Inflector;

interface WordInflector
{
    public function inflect(string $word) : string;
}
