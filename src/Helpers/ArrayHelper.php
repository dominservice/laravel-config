<?php

namespace Dominservice\LaravelConfig\Helpers;

class ArrayHelper
{
    /**
     * @param null $value
     * @return string
     */
    public static function valueTypeOf($value = null): string
    {
        if (!is_null($value) && $value !== '') {
            if (is_array($value) || is_array(json_decode($value, true))) {
                return 'array';
            }

            if (is_int($value) || preg_match('/^[-+]?[0-9]*$/', $value) || $value === 0) {
                return 'int';
            }

            if (preg_match('/^[-+]?(\d*\.\d+)$/', $value) || $value === '0.0' || $value === '0.00') {
                return 'float';
            }

            if (preg_match("/^(true|false)$/", is_bool($value) ? ($value ? 'true' : 'false') : $value)) {
                return 'bool';
            }
        }

        if (is_string($value)) {
            return 'string';
        }

        return 'undefined';
    }

    /**
     * @param null $value
     * @param null $type
     * @param bool $read
     * @return bool|false|float|int|mixed|string
     */
    public static function valueCastTo($value = null, $type = null, $read = true): mixed
    {
        switch ($type) {
            case 'int':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'bool':
                if ($value === 'true' || $value === true || (float)$value > 0) {
                    return $read === true ? true : 'true';
                }
                return $read === true ? false : 'false';
            case 'object':
                return $read == true ? json_decode($value) : json_encode($value);
            case 'array':
                return $read == true ? json_decode($value, true) : json_encode($value);
            case 'string':
            default:
                return (string)$value;
        }
    }

    /**
     * @param $arrays
     * @return array
     */
    private static function arrayMergeDeepArray($arrays): array
    {
        $result = array();
        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                // Renumber integer keys as array_merge_recursive() does. Note that PHP
                // automatically converts array keys that are integer strings (e.g., '1')
                // to integers.
                if (is_integer($key)) {
                    $result[] = $value;
                }
                elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                    $result[$key] = self::arrayMergeDeepArray(array(
                        $result[$key],
                        $value,
                    ));
                }
                else {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * @param ...$args
     * @return array
     */
    public static function arrayMergeDeep(...$args): array
    {
        return self::arrayMergeDeepArray($args);
    }
}
