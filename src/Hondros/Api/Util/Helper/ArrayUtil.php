<?php
/**
 * Created by PhpStorm.
 * User: Joey Rivera
 * Date: 7/19/2015
 * Time: 1:04 PM
 */

namespace Hondros\Api\Util\Helper;

trait ArrayUtil
{
    /**
     * Randomize array values while maintaining keys
     *
     * @param $array
     * @return mixed
     */
    function shuffleMaintainKeys($array)
    {
        $keys = array_keys($array);

        shuffle($keys);

        $new = [];
        foreach($keys as $key) {
            $new[$key] = $array[$key];
        }

        $array = $new;

        return $array;
    }
}