<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/12
 * Time: 16:09
 */
namespace moon;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;


class Router {

    public function match($methods, $pattern, $handler)
    {
        // TODO: Implement map() method.
    }

    public function dispatch(ServerRequestInterface $request)
    {
        // TODO: Implement dispatch() method.
    }

}