<?php

namespace CartThrob\Tags;

class SaveVaultFieldValueTag extends Tag
{
    public function process()
    {
        $encrypt = ee('Encrypt');

        return $encrypt->encode('y');
    }
}
