<?php

namespace CartThrob\Tags;

use CartThrob\Request\Request;
use EE_Session;

class DeleteFromCartTag extends Tag
{
    /** @var Request */
    private $request;

    public function __construct(EE_Session $session, Request $request)
    {
        parent::__construct($session);

        $this->request = $request;
    }

    public function process()
    {
        if (ee()->extensions->active_hook('cartthrob_delete_from_cart_start') === true) {
            ee()->extensions->call('cartthrob_delete_from_cart_start');
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        if ($this->param('row_id') !== false) {
            ee()->cartthrob->cart->remove_item($this->param('row_id'));
        } elseif ($this->hasParam('entry_id')) {
            $data = ['entry_id' => $this->param('entry_id')];

            foreach ($this->params() as $key => $value) {
                if (preg_match('/^item_options?:(.*)$/', $key, $match)) {
                    $data['item_options'][$match[1]] = $value;
                }
            }

            if ($this->request->has('item_options') && is_array($this->request->input('item_options'))) {
                $data['item_options'] = isset($data['item_options'])
                    ? array_merge($data['item_options'], $this->request->input('item_options'))
                    : $this->request->input('item_options');
            }

            if ($item = ee()->cartthrob->cart->find_item($data)) {
                $item->remove();
            }
        }

        if (ee()->extensions->active_hook('cartthrob_delete_from_cart_end') === true) {
            ee()->extensions->call('cartthrob_delete_from_cart_end');
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        ee()->cartthrob->cart->save();

        ee()->template_helper->tag_redirect($this->param('return'));
    }
}
