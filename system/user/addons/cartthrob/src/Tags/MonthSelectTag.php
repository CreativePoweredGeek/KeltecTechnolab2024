<?php

namespace CartThrob\Tags;

use EE_Session;

class MonthSelectTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->helper('form');
    }

    public function process()
    {
        $selected = null;
        $attrs = [];
        $data = [
            '01' => ee()->lang->line('january'),
            '02' => ee()->lang->line('february'),
            '03' => ee()->lang->line('march'),
            '04' => ee()->lang->line('april'),
            '05' => ee()->lang->line('may'),
            '06' => ee()->lang->line('june'),
            '07' => ee()->lang->line('july'),
            '08' => ee()->lang->line('august'),
            '09' => ee()->lang->line('september'),
            '10' => ee()->lang->line('october'),
            '11' => ee()->lang->line('november'),
            '12' => ee()->lang->line('december'),
        ];

        if ($this->hasParam('id')) {
            $attrs['id'] = $this->param('id');
        }

        if ($this->hasParam('class')) {
            $attrs['class'] = $this->param('class');
        }

        if ($this->hasParam('onchange')) {
            $attrs['onchange'] = $this->param('onchange');
        }

        $extra = '';

        if ($attrs) {
            $extra .= _attributes_to_string($attrs);
        }

        if ($this->hasParam('extra')) {
            if (substr($this->param('extra'), 0, 1) != ' ') {
                $extra .= ' ';
            }

            $extra .= $this->param('extra');
        }

        $name = $this->param('name', 'expiration_month');

        if ($this->hasParam('selected')) {
            $selected = $this->param('selected');
        }

        if (!$selected || !array_key_exists($selected, $data)) {
            $selected = @date('m');
        }

        return form_dropdown($name, $data, $selected, $extra);
    }
}
