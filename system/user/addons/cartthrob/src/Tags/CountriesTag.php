<?php

namespace CartThrob\Tags;

use EE_Session;

class CountriesTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('locales');
    }

    public function process()
    {
        $data = [];

        foreach (ee()->locales->countries($this->param('alpha2')) as $abbrev => $country) {
            $data[] = [
                'country_code' => $abbrev,
                'countries:country_code' => $abbrev,
                'country' => $country,
                'countries:country' => $country,
            ];
        }

        $order_by = $this->param('orderby', 'country');

        $order_by = array_key_exists($order_by, $data[0]) ? $order_by : 'country';

        usort($data, function ($a, $b) use ($order_by) {
            return $a[$order_by] <=> $b[$order_by];
        });

        return $this->parseVariables($data);
    }
}
