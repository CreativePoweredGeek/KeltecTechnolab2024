<?php

namespace CartThrob\Tags;

use Cartthrob_item;
use EE_Session;

class CartFormTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library(['number', 'form_builder', 'data_filter']);
    }

    public function process()
    {
        $data = $this->globalVariables(true);
        $data['items'] = [];

        /* @var CartThrob_item $item */
        foreach (ee()->cartthrob->cart->items() as $row_id => $item) {
            $data['items'][$row_id] = $item->data();
            $data['items'][$row_id]['entry_id'] = $item->product_id();

            $row['item_price:numeric'] =
            $row['price:numeric'] =
            $row['item_price_numeric'] =
            $row['price_numeric'] =
                $item->price();

            $row['item_price_plus_tax:numeric'] =
            $row['price_numeric:plus_tax'] =
            $row['price_plus_tax:numeric'] =
            $row['item_price_plus_tax_numeric'] =
            $row['price_plus_tax_numeric'] =
                $item->taxed_price();

            $row['item_price'] =
            $row['price'] =
                ee()->number->format($item->price());

            $row['item_price_plus_tax'] =
            $row['price:plus_tax'] =
            $row['item_price:plus_tax'] =
            $row['price_plus_tax'] =
                ee()->number->format($item->taxed_price());

            foreach ($this->itemOptionVars($item->product_id(), $row_id) as $key => $value) {
                $data['items'][$row_id][$key] = $value;
            }
        }

        $order_by = $this->hasParam('order_by') ? $this->param('order_by') : $this->param('orderby');

        ee()->data_filter->sort($data['items'], $order_by, $this->param('sort'));
        ee()->data_filter->limit($data['items'], $this->param('limit'), $this->param('offset'));

        ee()->form_builder->initialize([
            'form_data' => [
                'action',
                'secure_return',
                'return',
                'language',
            ],
            'encoded_form_data' => [],
            'encoded_numbers' => [],
            'encoded_bools' => [],
            'classname' => 'Cartthrob',
            'method' => 'cart_action',
            'params' => $this->params(),
            'content' => $this->parseVariablesRow($data),
        ]);

        return ee()->form_builder->form();
    }
}
