<?php

namespace CartThrob\Seeds\Fields\Order;

use CartThrob\Seeder\Core\AbstractSeed;
use CartThrob\Seeds\Fields\AbstractField;

class Items extends AbstractField
{
    public function read($value)
    {
        return $value;
    }

    public function fakieData(\Faker\Generator $faker, AbstractSeed $seed)
    {
        return;
    }
}
