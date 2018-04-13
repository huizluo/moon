<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/13
 * Time: 14:30
 */
namespace moon\router\interfaces;

use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface {

    const DISPATCH_STATUS = 0;
    const ALLOW_METHODS = 1;

    /**
     * add route
     * @param string $method
     * @param string $pattern
     * @param callable $callable
     *
     * @return RouterInterface
     * */
    public function match($method,$pattern,callable $callable);

    /**
     * dispatch router for http request
     * @param ServerRequestInterface $request
     * @return array
     * */
    public function dispatch(ServerRequestInterface $request);

    /**
     * Add a route group to the array
     *
     * @param string   $pattern The group pattern
     * @param callable $callable A group callable
     *
     * @return RouteGroupInterface
     */
    public function pushGroup($pattern, $callable);

    /**
     * Removes the last route group from the array
     *
     * @return bool True if successful, else False
     */
    public function popGroup();

    /**
     * Get named route object
     *
     * @param string $name        Route name
     *
     * @return RouteInterface
     *
     * @throws \RuntimeException   If named route does not exist
     */
    public function getNamedRoute($name);

    /**
     * @param $identifier
     *
     * @return RouteInterface
     */
    public function lookupRoute($identifier);

    /**
     * Build the path for a named route excluding the base path
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws \RuntimeException         If named route does not exist
     * @throws \InvalidArgumentException If required data not provided
     */
    public function relativePathFor($name, array $data = [], array $queryParams = []);

    /**
     * Build the path for a named route including the base path
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws \RuntimeException         If named route does not exist
     * @throws \InvalidArgumentException If required data not provided
     */
    public function pathFor($name, array $data = [], array $queryParams = []);
}