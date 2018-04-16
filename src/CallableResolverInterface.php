<?php
/**
 *
 */
namespace moon;

interface CallableResolverInterface
{
    /**
     * Invoke the resolved callable.
     *
     * @param mixed $toResolve
     *
     * @return callable
     */
    public function resolve($toResolve);
}