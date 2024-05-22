<?php

namespace CartThrob\Tags;

class SaveVaultCheckboxTag extends SaveVaultFieldValueTag
{
    public function process()
    {
        if (!$this->memberLoggedIn()) {
            return;
        }

        $value = parent::process();
        $field_id = $this->param('id');
        $extra = '';
        if ($field_id) {
            $extra = ' id="' . $field_id . '"';
        }

        $checked = bool_string($this->param('checked'));

        return form_checkbox('VLT', $value, $checked, $extra);
    }
}
