<?php

namespace CartThrob\Seeds\Fields;

use CartThrob\Seeder\Channels\AbstractField as SeederAbstractField;
use CartThrob\Seeder\Core\SeedInterface;

abstract class AbstractField extends SeederAbstractField implements SeedInterface
{
    /**
     * Just setup the CartThrob injection
     * AbstractField constructor.
     */
    public function __construct()
    {
        ee()->load->add_package_path(PATH_THIRD . 'cartthrob/');
    }
}
