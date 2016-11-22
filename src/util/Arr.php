<?php
/*
 *    Copyright 2012-2016 Youzan, Inc.
 *
 *    Licensed under the Apache License, Version 2.0 (the "License");
 *    you may not use this file except in compliance with the License.
 *    You may obtain a copy of the License at
 *
 *        http://www.apache.org/licenses/LICENSE-2.0
 *
 *    Unless required by applicable law or agreed to in writing, software
 *    distributed under the License is distributed on an "AS IS" BASIS,
 *    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *    See the License for the specific language governing permissions and
 *    limitations under the License.
 */
namespace rust\util;
use ArrayAccess;
use Closure;

/**
 * Class Arr
 * @package rust\util
 */
class Arr {
    public static function join(array $before, array $after) {
        if (empty($before)) {
            return $after;
        }
        if (empty($after)) {
            return $before;
        }
        foreach ($after as $row) {
            $before[] = $row;
        }
        return $before;
    }

    public static function sortByArray(array $arr, array $sort, $withNotExists = FALSE) {
        if (!$sort) {
            return $arr;
        }
        if (!$arr) {
            return [];
        }
        $ret = [];
        $notExist = [];
        $map = array_flip($arr);
        foreach ($sort as $item) {
            if (isset($map[$item])) {
                $ret[] = $item;
                unset($map[$item]);
            } else {
                $notExist[] = $item;
            }
        }
        if (!empty($map)) {
            $ret = Arr::join($ret, array_keys($map));
        }
        if (FALSE === $withNotExists) {
            return $ret;
        }
        return [
            'result'   => $ret,
            'notExist' => $notExist,
        ];
    }

    public static function merge() {
        $total = func_num_args();
        if (0 === $total) {
            return [];
        }
        $result = func_get_arg(0);
        for ($i = 1; $i < $total; $i++) {
            foreach (func_get_arg($i) as $key => $val) {
                if (!isset($result[$key])) {
                    $result[$key] = $val;
                    continue;
                }
                if (is_array($val) && is_array($result[$key])) {
                    $result[$key] = Arr::merge($result[$key], $val);
                } else {
                    $result[$key] = $val;
                }
            }
        }
        return $result;
    }

    public static function mapKey(array $array, $col) {
        $cols = self::array_cols($array, $col);
        if (empty($cols)) {
            return [];
        }
        return array_combine($cols, $array);
    }

    /**
     * @param      $arr
     * @param      $col
     * @param bool $default
     * @param bool $is_unique
     * @example
     * ======================================
     *  input:
     * --------------------------------------
     *  $arr = array(
     *      [ key1 => 1 , key2 => 2],
     *      [ key1 => 3 , key2 => 4],
     *      [ key1 => 5 , key2 => 6],
     *  );
     *  $col = key1;
     * ======================================
     * output
     * --------------------------------------
     * array( 1,3,5 );
     * @return array
     */
    public static function array_cols($arr, $col, $default = FALSE, $is_unique = FALSE) {
        if (empty($arr)) {
            return [];
        }
        $ret = [];
        foreach ($arr as $row) {
            if (isset($row[$col])) {
                $ret[] = $row[$col];
            } elseif (FALSE !== $default) {
                $ret[] = $default;
            }
        }
        if ($is_unique) {
            $ret = array_unique($ret);
        }
        return $ret;
    }

    /**
     * @param array $list
     * @param       $value
     * @return array
     *
     * @example Arr::createTreeByList(['a','b','c'],1);
     * @output  ['a' => [ 'b' => [ 'c' => 1 ] ] ]
     */
    public static function createTreeByList(array $list, $value) {
        if (empty($list)) {
            return $value;
        }
        $map = [];
        $first = array_shift($list);
        $map[$first] = self::createTreeByList($list, $value);
        return $map;
    }

    /**
     * Determine whether the given value is array accessible.
     *
     * @param  mixed $value
     * @return bool
     */
    public static function accessible($value) {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param  array  $array
     * @param  string $prepend
     * @return array
     */
    public static function dot($array, $prepend = '') {
        $results = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }
        return $results;
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param  \ArrayAccess|array $array
     * @param  string|int         $key
     * @return bool
     */
    public static function exists($array, $key) {
        if (is_array($array)) {
            return array_key_exists($key, $array);
        }
        return $array->offsetExists($key);
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  \ArrayAccess|array $array
     * @param  string             $key
     * @param  mixed              $default
     * @return mixed
     */
    public static function get($array, $key, $default = NULL) {
        if (is_null($key)) {
            return $array;
        }
        if (isset($array[$key])) {
            return $array[$key];
        }
        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return $default instanceof Closure ? $default() : $default;
            }
        }
        return $array;
    }

    /**
     * Check if an item exists in an array using "dot" notation.
     *
     * @param  \ArrayAccess|array $array
     * @param  string             $key
     * @return bool
     */
    public static function has($array, $key) {
        if (empty($array) || is_null($key)) {
            return FALSE;
        }
        if (array_key_exists($key, $array)) {
            return TRUE;
        }
        foreach (explode('.', $key) as $segment) {
            if ((is_array($array) && array_key_exists($segment, $array))
                || ($array instanceof ArrayAccess && $array->offsetExists($segment))
            ) {
                $array = $array[$segment];
            } else {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param  array  $array
     * @param  string $key
     * @param  mixed  $value
     * @return array
     */
    public static function set(&$array, $key, $value) {
        if (is_null($key)) {
            return $array = $value;
        }
        $keys = explode('.', $key);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }
        $array[array_shift($keys)] = $value;
        return $array;
    }

    /**
     * Filter the array using the given callback.
     *
     * @param  array    $array
     * @param  callable $callback
     * @return array
     */
    public static function where($array, callable $callback) {
        $filtered = [];
        foreach ($array as $key => $value) {
            if (call_user_func($callback, $key, $value)) {
                $filtered[$key] = $value;
            }
        }
        return $filtered;
    }

    /**
     * Get all of the given array except for a specified array of items.
     *
     * @param  array        $array
     * @param  array|string $keys
     * @return array
     */
    public static function except($array, $keys) {
        static::forget($array, $keys);
        return $array;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array        $array
     * @param  array|string $keys
     * @return void
     */
    public static function forget(&$array, $keys) {
        $original = &$array;
        $keys = (array)$keys;
        if (count($keys) === 0) {
            return;
        }
        foreach ($keys as $key) {
            $parts = explode('.', $key);
            // clean up before each pass
            $array = &$original;
            while (count($parts) > 1) {
                $part = array_shift($parts);
                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue;
                }
            }
            unset($array[array_shift($parts)]);
        }
    }
}
