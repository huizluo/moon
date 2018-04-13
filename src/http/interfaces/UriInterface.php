<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/13
 * Time: 13:11
 */
namespace moon\http\interfaces;

interface UriInterface extends \Psr\Http\Message\UriInterface{

    /**
     * get app root path
     * @return string basePath
     * */
    public function getBasePath();
}