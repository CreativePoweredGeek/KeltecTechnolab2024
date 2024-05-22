<?php

namespace CartThrob\Tags;

use EE_Session;

class AlsoPurchasedTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->model(['purchased_items_model', 'cartthrob_entries_model']);
    }

    public function process()
    {
        $data = [];

        if ($parent_id = $this->param('entry_id')) {
            $purchased = ee()->purchased_items_model->also_purchased($parent_id, $this->param('limit'));

            foreach ($purchased as $entry_id => $count) {
                if ($row = ee()->cartthrob_entries_model->entry_vars($entry_id)) {
                    $data[] = $row;
                }
            }
        }

        return $this->parseVariables($data);
    }
}
