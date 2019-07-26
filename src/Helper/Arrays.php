<?php

namespace SNOWGIRL_CORE\Helper;

class Arrays
{
    public static function mapByKeyKeyAndValueKey($input, $keyKey, $keyValue): array
    {
        $tmp = [];

        foreach ($input as $v) {
            $tmp[$v[$keyKey]] = $v[$keyValue];
        }

        return $tmp;
    }

    public static function groupByKeyMaker($input, \Closure $fnGroupValue): array
    {
        $tmp = [];

        foreach ($input as $item) {
            $key = $fnGroupValue($item);

            if (!isset($tmp[$key])) {
                $tmp[$key] = [];
            }

            $tmp[$key][] = $item;
        }

        return $tmp;
    }

    protected static function _getUniqueCombinations($input, $size, &$tmp, $index = 0, $current = [])
    {
        if ($index == count($input)) {
            return;
        }

        self::_getUniqueCombinations($input, $size, $tmp, $index + 1, $current);
        $current[] = $input[$index];

        if (count($current) === $size) {
            $tmp[] = $current;
        } else {
            self::_getUniqueCombinations($input, $size, $tmp, $index + 1, $current);
        }
    }

    public static function getUniqueCombinations(array $input): array
    {
        $tmp = [];

        for ($size = 1; $size < count($input) + 1; $size++) {
            self::_getUniqueCombinations($input, $size, $tmp);
        }

        return $tmp;
    }

    public static function mapByKeyMaker(array $input, \Closure $maker): array
    {
        if (!$input) {
            return [];
        }

        return array_combine(array_map(function ($k, $v) use ($maker) {
            return $maker($v, $k);
        }, array_keys($input), $input), $input);
    }

    public static function mapByValueMaker($input, \Closure $maker): array
    {
        $output = [];

        foreach ($input as $k => $v) {
            $output[$k] = $maker($v);
        }

        return $output;
    }

    /**
     * @todo new version of ::arrayAssoc
     *
     * @param array    $input
     * @param \Closure $maker
     *
     * @return array
     */
    public static function mapByKeyValueMaker(array $input, \Closure $maker): array
    {
        if (!$input) {
            return [];
        }

        $output = [];

        foreach ($input as $k => $v) {
            list($k, $v) = $maker($k, $v);
            $output[$k] = $v;
        }

        return $output;
    }

    public static function mergeRecursiveUnique(array $input0, array $input1): array
    {
        $arrays = func_get_args();
        $remains = $arrays;

        $output = [];

        foreach ($arrays as $array) {
            array_shift($remains);

            if (is_array($array)) {
                foreach ($array as $key => $value) {
                    if (is_array($value)) {
                        $args = [];

                        foreach ($remains as $remain) {
                            if (array_key_exists($key, $remain)) {
                                array_push($args, $remain[$key]);
                            }
                        }

                        if (count($args) > 2) {
                            $output[$key] = call_user_func_array(__FUNCTION__, $args);
                        } else {
                            foreach ($value as $key2 => $value2) {
                                $output[$key][$key2] = $value2;
                            }
                        }
                    } else {
                        $output[$key] = $value;
                    }
                }
            }
        }

        return $output;
    }

    public static function cast($input): array
    {
        if (!$input) {
            return [];
        }

        if (!is_array($input)) {
            return [$input];
        }

        return $input;
    }

    public static function sortByKeysArray(array $input, array $keys): array
    {
        $keys = is_array($keys) ? $keys : [$keys];

        $keys = array_filter($keys, function ($key) use ($input) {
            return isset($input[$key]);
        });

        return array_merge(array_flip($keys), $input);
    }

    public static function filterByKeysArray(array $input, array $keys): array
    {
        return array_filter($input, function ($key) use ($keys) {
            return in_array($key, $keys);
        }, ARRAY_FILTER_USE_KEY);
    }

    public static function filterByValuesArray(array $input, array $values): array
    {
        return array_filter($input, function ($value) use ($values) {
            return in_array($value, $values);
        });
    }

    public static function diffByKeysArray(array $input, array $keys): array
    {
        return array_filter($input, function ($key) use ($keys) {
            return !in_array($key, $keys);
        }, ARRAY_FILTER_USE_KEY);
    }

    public static function removeKeys(array $input, $keys): array
    {
        $keys = self::cast($keys);

        foreach ($input as $k => $v) {
            if (in_array($k, $keys)) {
                unset($input[$k]);
            }
        }

        return $input;
    }

    /**
     * Same as isset but returns true even if the values is NULL
     *
     * @param       $k
     * @param array $a
     *
     * @return bool
     */
    public static function _isset($k, array $a): bool
    {
        return isset($a[$k]) || array_key_exists($k, $a);
    }

    /**
     * Stable analog of usort function
     *
     * @param        $array
     * @param string $cmp_function
     */
    public static function userStableSort(&$array, $cmp_function = 'strcmp')
    {
        if (count($array) < 2) {
            return;
        }

        // Split the array in half
        $halfway = count($array) / 2;
        $array1 = array_slice($array, 0, $halfway);
        $array2 = array_slice($array, $halfway);

        // Recurse to sort the two halves
        self::userStableSort($array1, $cmp_function);
        self::userStableSort($array2, $cmp_function);

        // If all of $array1 is <= all of $array2, just append them.
        if (call_user_func($cmp_function, end($array1), $array2[0]) < 1) {
            $array = array_merge($array1, $array2);
            return;
        }

        // Merge the two sorted arrays into a single sorted array
        $array = [];
        $ptr1 = $ptr2 = 0;

        while ($ptr1 < count($array1) && $ptr2 < count($array2)) {
            if (call_user_func($cmp_function, $array1[$ptr1], $array2[$ptr2]) < 1) {
                $array[] = $array1[$ptr1++];
            } else {
                $array[] = $array2[$ptr2++];
            }
        }

        // Merge the remainder
        while ($ptr1 < count($array1)) {
            $array[] = $array1[$ptr1++];
        }

        while ($ptr2 < count($array2)) {
            $array[] = $array2[$ptr2++];
        }

        return;
    }

    public static function filterByLength(array $input): array
    {
        return array_filter($input, function ($v) {
            return strlen($v) > 0;
        });
    }

    public static function mapWithKeys(array $input, \Closure $maker): array
    {
        $output = [];

        foreach ($input as $k => $v) {
            $output[$k] = $maker($k, $v);
        }

        return $output;
    }
}