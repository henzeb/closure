<?php

namespace Henzeb\Closure;

use Closure;
use TypeError;

function closure(
    callable|string $callable,
    Closure         $resolve = null,
    bool            $allowBinding = false
): Closure
{
    if (is_callable($callable)) {
        return Closure::fromCallable($callable);
    }

    if (!class_exists($callable) || !method_exists($callable, '__invoke')) {
        throw new TypeError(
            sprintf(
                'Failed to create closure from callable: class "%s" does not exist or is not invokable',
                $callable
            )
        );
    }

    $resolveCallable = fn() => ($resolve ? $resolve($callable) : new $callable());


    if ($allowBinding) {
        $callable = $resolveCallable();
    } else {
        $callable = fn() => $resolveCallable()(...func_get_args());
    }

    return Closure::fromCallable($callable);
}

function bind(callable|string $callable, ?object $newThis, string $newScope = null): Closure
{
    return function() use ($callable, $newThis, $newScope) {
        return Closure::bind(
            closure($callable, allowBinding: true),
            $newThis,
            $newScope ?? $newThis
        )(...func_get_args());
    };
}

function call(callable|string $callable, ?object $newThis, string $newScope = null)
{
    return bind($callable, $newThis, $newScope)();
}
