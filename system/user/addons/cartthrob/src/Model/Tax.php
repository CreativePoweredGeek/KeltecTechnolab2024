<?php

namespace CartThrob\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * CartthrobTax Model
 */
class Tax extends Model
{
    protected static $_primary_key = 'id';
    protected static $_table_name = 'cartthrob_tax';

    protected $id;
    protected $tax_name;
    protected $percent;
    protected $shipping_is_taxable;
    protected $special;
    protected $state;
    protected $zip;
    protected $country;

    protected static $_validation_rules = [
        'tax_name' => 'required',
        'percent' => 'required|numeric',
    ];
}
