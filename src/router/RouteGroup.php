<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/13
 * Time: 15:47
 */
namespace moon\router;

use moon\App;
use moon\router\abstracts\AbstractRoute;
use moon\router\interfaces\RouteGroupInterface;

class RouteGroup extends AbstractRoute implements RouteGroupInterface
{
    /**
     * Create a new RouteGroup
     *
     * @param string   $pattern  The pattern prefix for the group
     * @param callable $callable The group callable
     */
    public function __construct($pattern, $callable)
    {
        $this->pattern = $pattern;
        $this->callable = $callable;
    }

    /**
     * Invoke the group to register any Routable objects within it.
     *
     * @param App $app The App instance to bind/pass to the group callable
     */
    public function __invoke(App $app = null)
    {
        $callable = $this->resolveCallable($this->callable);
        if ($callable instanceof Closure && $app !== null) {
            $callable = $callable->bindTo($app);
        }

        $callable($app);
    }

}