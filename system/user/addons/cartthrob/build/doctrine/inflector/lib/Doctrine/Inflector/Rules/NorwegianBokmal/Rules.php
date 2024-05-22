<?php

declare (strict_types=1);
namespace CartThrob\Dependency\Doctrine\Inflector\Rules\NorwegianBokmal;

use CartThrob\Dependency\Doctrine\Inflector\Rules\Patterns;
use CartThrob\Dependency\Doctrine\Inflector\Rules\Ruleset;
use CartThrob\Dependency\Doctrine\Inflector\Rules\Substitutions;
use CartThrob\Dependency\Doctrine\Inflector\Rules\Transformations;
final class Rules
{
    public static function getSingularRuleset() : Ruleset
    {
        return new Ruleset(new Transformations(...Inflectible::getSingular()), new Patterns(...Uninflected::getSingular()), (new Substitutions(...Inflectible::getIrregular()))->getFlippedSubstitutions());
    }
    public static function getPluralRuleset() : Ruleset
    {
        return new Ruleset(new Transformations(...Inflectible::getPlural()), new Patterns(...Uninflected::getPlural()), new Substitutions(...Inflectible::getIrregular()));
    }
}
