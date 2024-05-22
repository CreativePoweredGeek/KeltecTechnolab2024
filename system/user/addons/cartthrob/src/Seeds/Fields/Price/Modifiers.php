<?php

namespace CartThrob\Seeds\Fields\Price;

use CartThrob\Seeder\Core\AbstractSeed;
use CartThrob\Seeds\Fields\AbstractField;

class Modifiers extends AbstractField
{
    /**
     * @param mixed $value
     * @return mixed
     */
    public function read($value)
    {
        return @unserialize(base64_decode($value));
    }

    /**
     * @param \Faker\Generator $faker
     * @param AbstractSeed $seed
     * @return array
     */
    public function fakieData(\Faker\Generator $faker, AbstractSeed $seed)
    {
        $total = rand(1, 5);
        $return = [];
        for ($i = 0; $i <= $total; $i++) {
            $word = $faker->word();
            $return[] = [
                'option_value' => lcfirst($word),
                'option_name' => ucfirst($word),
                'price' => $faker->randomNumber(3),
            ];
        }

        return $return;
    }
}
