<?php

if (!function_exists('days')) {
    /**
     * @param $timestamp1
     * @param $timestamp2
     * @return string
     * @throws Exception
     */
    function days($timestamp1, $timestamp2)
    {
        $datetime1 = new DateTime($timestamp1);
        $datetime2 = new DateTime($timestamp2);
        $interval = $datetime1->diff($datetime2);

        return $interval->format('%R%a');
    }
}
