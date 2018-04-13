<?php
/**
 * author: lazar
 * Date: 2018/4/13
 * Time: 10:57
 */
namespace moon\http\interfaces;

use moon\CollectionInterface;

interface HeadersInterface extends CollectionInterface{

    public function add($key, $value);

    public function normalizeKey($key);
}