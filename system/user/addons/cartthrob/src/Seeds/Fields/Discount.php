<?php

namespace CartThrob\Seeds\Fields;

use CartThrob\Seeder\Core\AbstractSeed;
use CartThrob\Seeder\Core\SeedInterface;

class Discount extends AbstractField implements SeedInterface
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
     * Ok, so Discounts/Coupons are a tad complicated since they use
     *  different configurations depending on which CartThrob Discount Plugin
     *  is used. So we only fake 2 since those have matching.
     * @param \Faker\Generator $faker
     * @param AbstractSeed $seed
     * @return int|mixed
     */
    public function fakieData(\Faker\Generator $faker, AbstractSeed $seed)
    {
        $plugins = ['Cartthrob_discount_free_order', 'Cartthrob_discount_free_shipping'];

        return [
            'type' => $faker->randomElement($plugins),
            'used_by' => '',
            'per_user_limit' => '',
            'member_groups' => '',
            'member_ids' => '',
        ];
    }
}
