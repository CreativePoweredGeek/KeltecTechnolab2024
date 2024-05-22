<?php

namespace CartThrob\Tags;

class YearsTag extends Tag
{
    public function process()
    {
        $years = is_numeric($this->param('years')) ? $this->param('years') : 5;
        $start_year = is_numeric($this->param('start_year')) ? $this->param('start_year') : date('Y');
        $final_year = $start_year + $years;
        $data = [];

        for ($year = $start_year; $year < $final_year; $year++) {
            $data[] = ['year' => $year];
        }

        return $this->parseVariables($data);
    }
}
