<?php

namespace CartThrob\Math;

use CartThrob\Dependency\Webit\Util\EvalMath\EvalMath;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * CartThrob Math Class
 *
 * Uses EvalMath library to evalute arithmetic expressions
 */
class Math extends EvalMath
{
    private $errors = [];

    public function __construct()
    {
        parent::__construct();

        $this->suppress_errors = true;
    }

    /**
     * @param mixed $num1
     * @param mixed $num2
     * @param bool $operator
     * @return int|float|bool
     */
    public function arithmetic($num1, $num2 = 0, $operator = false)
    {
        if (!$operator) {
            $operator = '+';
        }

        $validOperators = ['+', '-', '*', '/', '%', '++', '--'];

        if (!in_array($operator, $validOperators)) {
            return $this->trigger(sprintf('Invalid Operator: %s', ee('Security/XSS')->clean($operator)));
        }

        if ($operator === '++') {
            $num2 = 1;
            $operator = '+';
        } elseif ($operator === '--') {
            $num2 = 1;
            $operator = '-';
        }

        if ($num1 === false || $num1 === '') {
            return $this->trigger('Missing/invalid num1');
        }

        if ($num2 === false || $num2 === '') {
            return $this->trigger('Missing/invalid num1');
        }

        $num1 = Number::sanitize($num1);
        $num2 = Number::sanitize($num2);
        $equation = sprintf('%s%s%s', $num1, $operator, $num2);

        return $this->evaluate($equation);
    }

    /**
     * @param string $expression
     * @return int|float|bool
     */
    public function evaluate($expression)
    {
        if (preg_match('#{.*?}#', $expression)) {
            return $this->trigger('Unparsed EE tags in expression, check parse order.');
        }

        return parent::evaluate($expression);
    }

    /**
     * @param $msg
     * @return bool
     */
    public function trigger($msg)
    {
        parent::trigger($msg);

        $this->errors[] = $this->last_error;

        return false;
    }

    /**
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }
}
