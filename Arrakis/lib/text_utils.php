<?php

class T
{
    public function __construct()
    {
    }

    /////////////////////////////////////////////////////////////////////// 

    public static function t($key)
    {
        return "%tr%$key";
    }

    public static function todo($value)
    {
        return $value;
    }
}

?>
