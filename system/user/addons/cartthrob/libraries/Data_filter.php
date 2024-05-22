<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

if (!class_exists('Data_filter')) {
    /**
     * Data Filter
     *
     * helps you sort, filter and limit multi-dimensional arrays, like the array you feed to parse_variables
     */
    class Data_filter
    {
        private $column;
        private $direction;
        private $value;
        private $operator = '==';
        private $valid_operators = [
            '==',
            '!=',
            '===',
            '!==',
            '>',
            '<',
            '>=',
            '<=',
            '<>',
            'in_array',
            'IN',
            'NOT_IN',
            'CONTAINS',
            'CONTAINS_ONE_OF',
            'CONTAINS_ALL_OF',
            'DOES_NOT_CONTAIN',
            'DOES_NOT_CONTAIN_ONE_OF',
            'DOES_NOT_CONTAIN_ALL_OF',
            'STARTS_WITH',
            'ENDS_WITH',
        ];

        /**
         * sort multidimensional arrays
         *
         * @param array &$array the array to sort, by reference
         * @param mixed $column the column on which to sort
         * @param string $direction asc or desc
         *
         * @return $this
         */
        public function sort(array &$array, $column, $direction = 'asc')
        {
            if (!$column && !$direction) {
                return $this;
            }

            $this->set_column($column)->set_direction($direction);

            usort($array, [$this, 'compare']);

            return $this;
        }

        /**
         * get an array of the values from a certain column in a multidimensional array
         *
         * ex:
         * $array = array(
         *    array(
         *        'name' => 'foo',
         *        'kind' => 'video',
         *    ),
         *    array(
         *        'name' => 'bar',
         *        'kind' => 'audio',
         *    ),
         *  );
         *
         *  ee()->data_filter->key_values($array, 'name')
         *
         *  array('foo', 'bar');
         *
         * @param array &$array
         * @param mixed $key the column whose values to collect
         *
         * @return array
         */
        public function key_values(array $array, $key)
        {
            $values = [];

            foreach ($array as $value) {
                if (isset($value[$key])) {
                    $values[] = $value[$key];
                }
            }

            return $values;
        }

        /**
         * filter out rows from a multidimensional array that match a certain value
         *
         * @param array &$array
         * @param mixed $column the column whose value you wish to check
         * @param mixed $value the value you wish to match
         * @param string $operator must be one of the operators set in Data_filter::$valid_operators
         *
         * ex:
         * $array = array(
         *    array(
         *        'name' => 'foo',
         *        'kind' => 'video',
         *    ),
         *    array(
         *        'name' => 'bar',
         *        'kind' => 'audio',
         *    ),
         *  );
         *
         *  ee()->data_filter->filter($array, 'name', 'foo');
         *
         *  array(
         *    array(
         *        'name' => 'foo',
         *        'kind' => 'video',
         *    ),
         *  )
         *
         * @param bool $reset_keys
         * @return $this
         */
        public function filter(array &$array, $column, $value, $operator = '==', $reset_keys = false)
        {
            $this
                ->set_column($column)
                ->set_operator($operator)
                ->set_value($value);

            if (is_array($array)) {
                $array = array_filter($array, [$this, 'match']);
            }

            if ($reset_keys) {
                $array = array_values($array);
            }

            return $this;
        }

        /**
         * slice an array
         *
         * @param array &$array
         * @param string|int $limit
         * @param string|int $offset
         *
         * @return $this
         */
        public function limit(array &$array, $limit, $offset = 0)
        {
            if ($limit === false && $offset === false) {
                return $this;
            }

            if (!is_numeric($offset)) {
                $offset = 0;
            }

            if (!is_numeric($limit)) {
                $limit = count($array);
            }

            $array = array_slice($array, $offset, $limit);

            return $this;
        }

        /**
         * @param $column
         * @return $this
         */
        private function set_column($column)
        {
            $this->column = $column;

            return $this;
        }

        /**
         * @param $operator
         * @return $this
         */
        private function set_operator($operator)
        {
            if (!in_array($operator, $this->valid_operators)) {
                $operator = $this->valid_operators[0];
            }

            $this->operator = $operator;

            return $this;
        }

        /**
         * @param $direction
         * @return $this
         */
        private function set_direction($direction)
        {
            $this->direction = $direction;

            return $this;
        }

        /**
         * @param $value
         * @return $this
         */
        private function set_value($value)
        {
            $this->value = $value;

            return $this;
        }

        /**
         * @param $row
         * @return bool
         */
        private function match($row)
        {
            $a = $row[$this->column] ?? null;
            $b = $this->value;

            switch ($this->operator) {
                case '==':
                    return $a == $b;
                case '<>':
                case '!=':
                    return $a != $b;
                case '===':
                    return $a === $b;
                case '!==':
                    return $a !== $b;
                case '>':
                    return $a > $b;
                case '<':
                    return $a < $b;
                case '>=':
                    return $a >= $b;
                case '<=':
                    return $a <= $b;
                case 'in_array':// legacy
                case 'IN':
                    return in_array($a, is_array($b) ? $b : explode('|', $b));
                case 'NOT_IN':
                    return !in_array($a, is_array($b) ? $b : explode('|', $b));
                case 'CONTAINS':
                    return (is_string($a) && strstr($b, $a)) || (!is_string($a) && $a === $b);
                case 'DOES_NOT_CONTAIN':
                    return (is_string($a) && !strstr($b, $a)) || (!is_string($a) && $a !== $b);
                case 'DOES_NOT_CONTAIN_ONE_OF':
                    $array = is_array($b) ? $b : explode('|', $b);
                    foreach ($array as $b) {
                        if (strstr($b, $a)) {
                            return false;
                        }
                    }

                    return true;
                case 'DOES_NOT_CONTAIN_ALL_OF':
                    $array = is_array($b) ? $b : explode('|', $b);
                    foreach ($array as $b) {
                        if (!strstr($b, $a)) {
                            return true;
                        }
                    }

                    return false;
                case 'CONTAINS_ALL_OF':
                    $array = is_array($b) ? $b : explode('|', $b);
                    foreach ($array as $b) {
                        if (!strstr($b, $a)) {
                            return false;
                        }
                    }

                    return true;
                case 'CONTAINS_ONE_OF':
                    $array = is_array($b) ? $b : explode('|', $b);
                    foreach ($array as $b) {
                        if (strstr($b, $a)) {
                            return true;
                        }
                    }

                    return false;
                case 'STARTS_WITH':
                    return strncasecmp($a, $b, strlen($b)) === 0;
                case 'ENDS_WITH':
                    return (bool)preg_match('/' . preg_quote($b) . '$/', $a);
            }
        }

        /**
         * @param $a
         * @param $b
         * @return int
         */
        public function compare($a, $b)
        {
            if (!$this->column) {
                return 0;
            }

            $x = (isset($a[$this->column])) ? strtolower($a[$this->column]) : null;
            $y = (isset($b[$this->column])) ? strtolower($b[$this->column]) : null;

            if ($x === $y) {
                return 0;
            }

            $compare = (strtolower($this->direction) == 'desc') ? ($x < $y) : ($x > $y);

            return ($compare) ? 1 : -1;
        }
    }
}
