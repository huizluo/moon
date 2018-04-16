<?php
/**
 *callable resolver
 * support callable function and [class,'method'] and :class+':method'
 */
namespace moon;

use Psr\Container\ContainerInterface;

class CallableResolver implements CallableResolverInterface
{
    const PATTERN = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function resolve($toResolve)
    {
        /**
         * toResolve is callable function or array[class,'method']
         * */
        if (is_callable($toResolve)) {

            if (is_array($toResolve)){
                $resolved = $this->resolveCallable($toResolve[0],$toResolve[1]);
                $this->assertCallable($resolved);

                return $resolved;
            }
            return $toResolve;
        }

        if (!is_string($toResolve)) {
            $this->assertCallable($toResolve);
        }

        // check callable as "class:method"
        if (preg_match(self::PATTERN, $toResolve, $matches)) {
            $resolved = $this->resolveCallable($matches[1], $matches[2]);
            $this->assertCallable($resolved);

            return $resolved;
        }

        $resolved = $this->resolveCallable($toResolve);
        $this->assertCallable($resolved);

        return $resolved;
    }

    protected function resolveCallable($class, $method = '__invoke')
    {

        if ($this->container->has($class)) {
            return [$this->container->get($class), $method];
        }

        if (!class_exists($class)) {
            throw new \RuntimeException(sprintf('Callable %s does not exist', $class));
        }

        return [new $class($this->container), $method];//类实例+方法组成的数组就是一个 回调？？
    }

    protected function assertCallable($callable)
    {
        if (!is_callable($callable)) {
            throw new \RuntimeException(sprintf(
                '%s is not resolvable',
                is_array($callable) || is_object($callable) ? json_encode($callable) : $callable
            ));
        }
    }

}