<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

use CartThrob\Math\Number;
use CartThrob\Plugins\Tax\TaxPlugin;

class Cartthrob_tax_by_class extends TaxPlugin
{
    public $title = 'tax_by_class';
    public $note = 'tax_by_class_note';
    public $settings = [
        [
            'type' => 'add_to_head',
            'default' => '
                <script type="text/javascript">
        
                    jQuery(document).ready(function($){

                        $(".tax_class_select").each(function (i) {
                            var current = $(this).val(); 
                         
                                $(this).replaceWith(function() {
                                    var content = "<select name=\'"+$(this).attr("name")+"\' class=\'tax_class_select\' >"; 
                                    content += "<option value=\'"+current+"\'>"+current+"</option>"; 
                                    content += "</select>"; 
                                  return content; 
                                })
                                
                        });
                        
                        $(".new_class").each(function (i) {
                            if ($(this).val())
                            {
                                $(".tax_class_select").append("<option value=\'"+$(this).val()+"\'>"+$(this).val()+"</option>"); 
                            }
                        });

                        $(document).on("change", ".new_class", function(){
                            
                            if ($(".tax_class_select option[value="+$(this).val()+"]").length == 0 && $(this).val().length > 0)
                            {
                                var options = null; 
                                
                                $(".new_class").each(function (i) {
                                    if ($(this).val())
                                    {
                                        options += "<option value=\'"+$(this).val()+"\'>"+$(this).val()+"</option>"; 
                                    }
                                });
                                
                                $(".tax_class_select").each(function (i) {
                                    var current = $(this).val(); 

                                        $(this).replaceWith(function() {
                                            var content = "<select name=\'"+$(this).attr("name")+"\' class=\'tax_class_select\' >"; 
                                            content += "<option value=\'"+current+"\'>"+current+"</option>"; 
                                            content += options; 
                                            content += "</select>"; 
                                          return content; 
                                        }); 

                                });
                                
                                /* $(".tax_class_select").append("<option value=\'"+$(this).val()+"\'>"+$(this).val()+"</option>"); */
                            }
                        }); 
                    }); 
                </script>
            ',
            'name' => 'add_to_head',
            'short_name' => 'tax_by_class_add_to_head',
        ],
        [
            'name' => 'discount_tax_rate',
            'note' => 'discount_tax_rate_note',
            'short_name' => 'discount_tax_rate',
            'type' => 'text',
            'default' => '8',
        ],
        [
            'name' => 'tax_classes',
            'short_name' => 'tax_classes',
            'type' => 'matrix',
            'settings' => [
                [
                    'name' => 'tax_class',
                    'type' => 'text',
                    'short_name' => 'tax_class',
                    'attributes' => [
                        'class' => 'new_class',
                    ],
                ],
            ],
        ],
        [
            'name' => 'tax_by_location_settings',
            'short_name' => 'tax_settings',
            'type' => 'matrix',
            'settings' => [
                [
                    'name' => 'name',
                    'short_name' => 'name',
                    'type' => 'text',
                ],
                [
                    'name' => 'tax_percent',
                    'short_name' => 'rate',
                    'type' => 'text',
                ],
                [
                    'name' => 'country',
                    'short_name' => 'country',
                    'type' => 'select',
                    'options' => [],
                    'attributes' => [
                        'class' => 'countries_blank',
                    ],
                ],
                [
                    'name' => 'state',
                    'short_name' => 'state',
                    'type' => 'select',
                    'options' => [],
                    'attributes' => [
                        'class' => 'states_blank',
                    ],
                ],
                [
                    'name' => 'tax_class',
                    'short_name' => 'tax_class',
                    'type' => 'text',
                    'attributes' => [
                        'class' => 'tax_class_select',
                    ],
                ],
                [
                    'name' => 'tax_shipping',
                    'short_name' => 'tax_shipping',
                    'type' => 'checkbox',
                ],
            ],
        ],
    ];

    protected $tax_data;

    /**
     * @param $price
     * @return mixed|string
     */
    public function get_tax($price)
    {
        $args = func_get_args();

        if (count($args) <= 1 || !isset($args[1])) {
            return $this->core->round($price * $this->tax_rate());
        }

        if (is_object($args[1])) {
            $item = $args[1];

            $prefix = ($this->core->store->config('tax_use_shipping_address')) ? 'shipping_' : '';

            foreach ($this->plugin_settings('tax_settings', []) as $tax_data) {
                $tax_data['tax_class'] = $tax_data['tax_class'] ?? null;
                $tax_data['country'] = $tax_data['country'] ?? null;
                $tax_data['state'] = $tax_data['state'] ?? null;

                if ($this->core->cart->customer_info($prefix . 'state') && $tax_data['state'] == $this->core->cart->customer_info($prefix . 'state') && $item->item_options('tax_class') == $tax_data['tax_class']) {
                    $this->tax_data = $tax_data;

                    return $this->core->round($price * $this->tax_rate());
                } elseif ($this->core->cart->customer_info($prefix . 'country_code') && $tax_data['country'] == $this->core->cart->customer_info($prefix . 'country_code') && $item->item_options('tax_class') == $tax_data['tax_class']) {
                    $this->tax_data = $tax_data;

                    return $this->core->round($price * $this->tax_rate());
                } elseif (isset($tax_data['tax_class']) && $tax_data['tax_class'] == 'GLOBAL') {
                    // this doesn't account for 2 global classes
                    $this->tax_data = $tax_data;

                    return $this->core->round($price * $this->tax_rate());
                }
            }
        } elseif ($args[1] == 'discount' && $this->plugin_settings('discount_tax_rate')) {
            $this->tax_data['rate'] = $this->plugin_settings('discount_tax_rate');

            return $this->core->round($price * $this->tax_rate());
        }

        return $this->core->round($price * $this->tax_rate());
    }

    /**
     * @return float|int
     */
    public function tax_rate()
    {
        return abs(Number::sanitize($this->tax_data('rate')) / 100);
    }

    /**
     * @param bool $key
     * @return bool|null
     */
    public function tax_data($key = false)
    {
        if (is_null($this->tax_data)) {
            $this->tax_data = null;
        }

        if ($key === false) {
            return $this->tax_data;
        }

        return (isset($this->tax_data[$key])) ? $this->tax_data[$key] : false;
    }

    /**
     * @return bool|null
     */
    public function tax_name()
    {
        return $this->tax_data('name');
    }

    /**
     * @return bool
     */
    public function tax_shipping()
    {
        return (bool)$this->tax_data('tax_shipping');
    }
}
