<?php

namespace Henzeb\Closure;

use Closure;
use Henzeb\Closure\Support\ClosureBinding;
use Henzeb\Closure\Support\InvokableReflection;
use ReflectionException;
use TypeError;

function closure(
    callable|object|string $callable,
    callable $resolve = null,
    string $invoke = null
): Closure {
    if (is_callable($callable)
        && !InvokableReflection::returnTypeIsClosure($callable, $invoke)
    ) {
        return Closure::fromCallable($callable);
    }

    if (!InvokableReflection::invokable($callable, $invoke)) {
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

    if (InvokableReflection::returnTypeIsClosure($callable, $invoke)) {
        return $resolveInvokable()->$invoke();
    }

    return Closure::fromCallable(
        function () use ($resolveInvokable, $invoke) {
            static $resolved;
            $resolved ??= $resolveInvokable();
            return $resolved->$invoke(...func_get_args());
        }
    );
}

function wrap(
    mixed $callable,
    callable $resolve = null,
    string $invoke = null
): Closure {
    if (!is_callable($callable)
        && !InvokableReflection::invokable($callable, $invoke)
    ) {
        return fn() => $callable;
    }

    return closure($callable, $resolve, $invoke);
}

function bind(
    callable|object|string $callable,
    ?object $newThis,
    string $newScope = null,
    callable $resolve = null,
    string $invoke = null,
): Closure {
    return closure(
        $callable,
        $resolve,
        $invoke
    )->bindTo(
        $newThis,
        $newScope ?? $newThis
    );
}

function call(
    callable|object|string $callable,
    object $newThis = null,
    string $newScope = null,
    callable $resolve = null,
    string $invoke = null
): mixed {
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
    callable $resolve = null,
    string $invoke = null
): ClosureBinding {
    return new ClosureBinding(
        closure($callable, $resolve, $invoke)
    );
}

function invokable(mixed $object, string $invoke = null): bool
{
    return InvokableReflection::invokable($object, $invoke);
}
