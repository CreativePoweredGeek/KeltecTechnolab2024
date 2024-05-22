<?php

declare (strict_types=1);
namespace CartThrob\Dependency\Doctrine\Inflector\Rules\Spanish;

use CartThrob\Dependency\Doctrine\Inflector\GenericLanguageInflectorFactory;
use CartThrob\Dependency\Doctrine\Inflector\Rules\Ruleset;
final class InflectorFactory extends GenericLanguageInflectorFactory
{
    protected function getSingularRuleset() : Ruleset
    {
        return Rules::getSingularRuleset();
    }
    protected function getPluralRuleset() : Ruleset
    {
        return Rules::getPluralRuleset();
    }
}
