<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License.
 * Redistributions of files MUST retain the above copyright notice.
 */

namespace Dida\Db;

class Util
{
    const VERSION = '20171113';


    public static function arrayAssocBy(array &$array, $keyN)
    {
        if (!$array) {
            return $array;
        }

        $args = func_get_args();
        array_shift($args);
        if (is_array($keyN)) {
            $args = $keyN;
        }

        $return = [];

        while ($row = array_shift($array)) {
            $cur = &$return;

            foreach ($args as $arg) {
                if (!array_key_exists($arg, $row)) {
                    return false;
                }

                $key = $row[$arg];

                if (!array_key_exists($key, $cur)) {
                    $cur[$key] = [];
                }
                $cur = &$cur[$key];
            }


            $cur = $row;
        }

        return $return;
    }


    public static function arrayGroupBy(array &$array, $keyN)
    {
        if (!$array) {
            return $array;
        }

        $args = func_get_args();
        array_shift($args);
        if (is_array($keyN)) {
            $args = $keyN;
        }

        $return = [];

        while ($row = array_shift($array)) {
            $cur = &$return;

            foreach ($args as $arg) {
                if (!array_key_exists($arg, $row)) {
                    return false;
                }

                $key = $row[$arg];

                if (!array_key_exists($key, $cur)) {
                    $cur[$key] = [];
                }
                $cur = &$cur[$key];
            }


            $cur[] = $row;
        }

        return $return;
    }
}
