<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/13
 * Time: 11:02
 */
namespace moon;

interface CollectionInterface extends \ArrayAccess,\Countable,\IteratorAggregate{

    public function set($key, $value);

    public function get($key, $default = null);

    public function replace(array $items);

    public function all();

    public function has($key);

    public function remove($key);

    public function clear();
}