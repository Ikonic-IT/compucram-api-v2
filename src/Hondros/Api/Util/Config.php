<?php

namespace Hondros\Api\Util;

use Laminas\Config\Config as LaminasConfig;

class Config
{
    public static function init($phpunit = false)
    {
        // check for override and merge
        $data = require "config/global.php";
        
        if (!$phpunit && is_file("config/local.php")) {
            $data = array_replace_recursive($data, require "config/local.php");
        } else if (!$phpunit) {
            throw new \Exception("local.php config file not found.");
        }

        if ($phpunit && is_file("config/phpunit.php")) {
            $data = array_replace_recursive($data, require "config/phpunit.php");
        } else if ($phpunit) {
            throw new \Exception("phpunit.php config file not found.");
        }
        
        return new LaminasConfig($data);
    }
}