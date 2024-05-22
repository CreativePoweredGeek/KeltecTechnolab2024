<?php

namespace CartThrob\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * Vault Model
 */
class Vault extends AbstractModel
{
    /**
     * @todo Add rules to validate related data
     * @var string[]
     */
    protected static $_validation_rules = [
        'member_id' => 'required|numeric|validateMemberExists',
        'token' => 'required',
        'order_id' => 'numeric',
        'gateway' => 'required',
    ];

    protected static $_primary_key = 'id';
    protected static $_table_name = 'cartthrob_vault';

    protected $id;
    protected $name;
    protected $member_id;
    protected $order_id;
    protected $token;
    protected $gateway;
    protected $customer_id;
    protected $exp_month;
    protected $exp_year;
    protected $first_name;
    protected $last_name;
    protected $address;
    protected $address2;
    protected $city;
    protected $state;
    protected $zip;
    protected $country;
    protected $shipping_first_name;
    protected $shipping_last_name;
    protected $shipping_address;
    protected $shipping_address2;
    protected $shipping_city;
    protected $shipping_state;
    protected $shipping_zip;
    protected $shipping_country;
    protected $primary;
    protected $created_date;
    protected $modified;
    protected $last_four;

    protected static $_relationships = [
        'Member' => [
            'type' => 'BelongsTo',
            'model' => 'ee:Member',
            'from_key' => 'member_id',
            'to_key' => 'member_id',
        ],
        'Entry' => [
            'type' => 'BelongsTo',
            'model' => 'ee:ChannelEntry',
            'from_key' => 'order_id',
            'to_key' => 'entry_id',
        ],
    ];
}
