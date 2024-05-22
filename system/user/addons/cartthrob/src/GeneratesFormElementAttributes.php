<?php

namespace CartThrob;

trait GeneratesFormElementAttributes
{
    /**
     * @param string|null $id
     * @param string|null $class
     * @param string|null $onchange
     * @param string|null $extra
     * @return string
     */
    private function generateFormAttrs($id = null, $class = null, $onchange = null, $extra = null)
    {
        $attrs = [];
        $str = '';

        if (!is_null($id)) {
            $attrs['id'] = $id;
        }

        if (!is_null($class)) {
            $attrs['class'] = $class;
        }

        if (!is_null($onchange)) {
            $attrs['onchange'] = $onchange;
        }

        if ($attrs) {
            $str .= _attributes_to_string($attrs);
        }

        if (!is_null($extra)) {
            if (substr($extra, 0, 1) !== ' ') {
                $str .= ' ';
            }

            $str .= $extra;
        }

        return $str;
    }
}
