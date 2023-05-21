<?php

namespace Henzeb\Closure;

use Closure;
use Henzeb\Closure\Support\ClosureBinding;
use Henzeb\Closure\Support\InvokableReflection;
use ReflectionException;
use TypeError;

function closure(
    callable|object|string $callable,
    callable               $resolve = null,
    string                 $invoke = null
): Closure
{
    if (is_callable($callable)) {
        return Closure::fromCallable($callable);
    }

    if (InvokableReflection::isValid($callable, $invoke)) {
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

    $invoke = InvokableReflection::getInvokeMethod($invoke);

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

        if (InvokableReflection::returnTypeIsClosure($callable)) {
            $closure = $closure();
        }

        return $closure->bindTo(
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
    if (null === $newThis && null === $newScope) {
        return closure($callable)();
    }

    return bind($callable, $newThis, $newScope, $resolve, $invoke)();
}


/**
 * @throws ReflectionException
 */
function binding(
    callable|object|string $callable,
    callable               $resolve = null,
    string                 $invoke = null
): ClosureBinding
{
    $closure = closure($callable, $resolve, $invoke);

    if (InvokableReflection::returnTypeIsClosure($callable, $invoke)) {
        $closure = $closure();
    }

    return new ClosureBinding(
        $closure
    );
}
