<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/13
 * Time: 14:29
 */
namespace moon\router\interfaces;

use moon\App;

interface RouteGroupInterface{

    /**
     * return route pattern
     *
     * @return string
     * */
    public function getPattern();

    /**
     * prepend middleware to the group middleware collection
     * @param callable|string $callable
     * @return RouteGroupInterface
     * */
    public function add($callable);

    public function __invoke(App $app);
}