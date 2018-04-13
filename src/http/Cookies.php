<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/13
 * Time: 14:33
 */
namespace moon\http;

use moon\http\interfaces\CookiesInterface;

class Cookies implements CookiesInterface
{
    public function get($name, $default = null)
    {
        // TODO: Implement get() method.
    }

    public function set($name, $value)
    {
        // TODO: Implement set() method.
    }

    public function toHeaders()
    {
        // TODO: Implement toHeaders() method.
    }

    public static function parseHeader($header)
    {
        return [];
    }

}