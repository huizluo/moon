<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/13
 * Time: 14:23
 */
namespace moon\handlers;

use moon\handlers\interfaces\InvokeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestResponse implements InvokeInterface
{
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    )
    {
        foreach ($routeArguments as $k => $v) {
            $request = $request->withAttribute($k, $v);
        }

        return call_user_func($callable, $request, $response, $routeArguments);
    }

}