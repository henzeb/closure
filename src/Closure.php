<?php

namespace Henzeb\Closure;

use Closure;
use ReflectionClass;
use TypeError;

function closure(
    callable|object|string $callable,
    Closure                $resolve = null,
    string                 $invoke = null
): Closure
{
    if (is_callable($callable)) {
        return Closure::fromCallable($callable);
    }

    $invoke = $invoke ?? '__invoke';

    if ((!is_object($callable) && !class_exists($callable))
        || !method_exists($callable, $invoke)) {
        throw new TypeError(
            sprintf(
                'Failed to create closure from callable: class "%s" does not exist or is not invokable',
                is_object($callable) ? $callable::class : $callable
            )
        );
    }

    $resolveInvokable = function () use ($resolve, $callable) {
        if (is_object($callable)) {
            return $callable;
        }
        return ($resolve ? $resolve($callable) : new $callable());
    };

    return Closure::fromCallable(
        fn() => $resolveInvokable()->$invoke(...func_get_args())
    );
}

function bind(
    callable|object|string $callable,
    ?object                $newThis,
    string                 $newScope = null,
    callable               $resolve = null,
    string                 $invoke = null,
): Closure
{
    return function () use ($callable, $newThis, $newScope, $resolve, $invoke) {
        $closure = closure($callable, resolve: $resolve, invoke: $invoke);

        if (is_string($callable) || is_object($callable)) {
            $returnType = (new ReflectionClass($callable))
                ->getMethod($invoke ?? '__invoke')
                ->getReturnType();

            if ($returnType?->getName() === Closure::class) {
                $closure = $closure();
            }
        }

        return Closure::bind(
            $closure,
            $newThis,
            $newScope ?? $newThis
        )(...func_get_args());
    };
}

function call(
    callable|object|string $callable,
    object                 $newThis = null,
    string                 $newScope = null,
    callable               $resolve = null,
    string                 $invoke = null
): mixed
{
    if (null === $newThis) {
        return closure($callable)();
    }

    return bind($callable, $newThis, $newScope, $resolve, $invoke)();
}
