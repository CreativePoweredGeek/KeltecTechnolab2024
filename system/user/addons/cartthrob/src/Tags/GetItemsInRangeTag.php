<?php

namespace CartThrob\Tags;

use CartThrob\Request\Request;
use EE_Session;
use ExpressionEngine\Library\Security\XSS;

class GetItemsInRangeTag extends Tag
{
    /** @var Request */
    private $request;
    /** @var XSS */
    private $xss;

    public function __construct(EE_Session $session, Request $request, XSS $xss)
    {
        parent::__construct($session);

        $this->request = $request;
        $this->xss = $xss;

        ee()->load->model('product_model');
    }

    /**
     * Returns string of entry_id's separated by | for use in weblog:entries
     */
    public function process()
    {
        $price_min = $this->hasParam('price_min') ? $this->xss->clean($this->param('price_min')) : $this->request->input('price_min');
        $price_max = $this->hasParam('price_max') ? $this->xss->clean($this->param('price_max')) : $this->request->input('price_max');

        if (!is_numeric($price_min)) {
            $price_min = '';
        }

        if (!is_numeric($price_max)) {
            $price_max = '';
        }

        if ($price_min == '' && $price_max == '') {
            return '';
        }

        $entry_ids = ee()->product_model->get_products_in_price_range($price_min, $price_max);

        if (count($entry_ids)) {
            return implode('|', $entry_ids);
        } else {
            return null;
        }
    }
}
