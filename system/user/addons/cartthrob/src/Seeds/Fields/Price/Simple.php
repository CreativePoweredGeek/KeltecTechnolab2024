<?php

namespace CartThrob\Seeds\Fields\Price;

use CartThrob\Seeder\Core\AbstractSeed;
use CartThrob\Seeds\Fields\AbstractField;

class Simple extends AbstractField
{
    /**
     * @param \Faker\Generator $faker
     * @param AbstractSeed $seed
     * @return int
     */
    public function fakieData(\Faker\Generator $faker, AbstractSeed $seed)
    {
        return $faker->randomFloat(2);
    }
}
