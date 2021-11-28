<?php
/*
 * creator: maigohuang
 * */

namespace App\Plugins\ImageX\Volc\Base;

use Exception;

class Singleton
{
    private static $instances = array();
    public function __construct()
    { }
    public function __clone()
    { }
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize');
    }

    public static function getInstance($region = 'cn-north-1')
    {
        $cls = get_called_class();
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static($region);
        }
        return self::$instances[$cls];
    }
}
