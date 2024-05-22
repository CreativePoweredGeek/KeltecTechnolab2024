<?php

namespace CartThrob\Tags;

class CartShippingPlusTaxTag extends Tag
{
    /**
     * Returns total shipping price for cart plus tax
     */
    public function process()
    {
        $value = ee()->cartthrob->cart->shipping_plus_tax();

        if (tag_param_equals(2, 'numeric')) {
            return $value;
        }

        return sprintf('%s%s',
            $this->param('prefix', '$'),
            number_format(
                $value,
                $decimals = (int)$this->param('decimals', 2),
                $decimalPoint = $this->param('dec_point', '.'),
                $thousandSeparator = $this->param('thousands_sep', ',')
            )
        );
    }
}
