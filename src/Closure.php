<?php

namespace Henzeb\Closure;

use Closure;
use Henzeb\Closure\Support\ClosureBinding;
use Henzeb\Closure\Support\Invokable;
use ReflectionException;
use TypeError;

function closure(
    callable|object|string $callable,
    callable $resolve = null,
    string $invoke = null
): Closure {
    if (is_callable($callable)
        && !Invokable::returnsClosure($callable, $invoke)
    ) {
        return Closure::fromCallable($callable);
    }

    $invoke = Invokable::getInvokeMethod($invoke);

    if (!Invokable::isInvokable($callable, $invoke)) {
        throw new TypeError(
            sprintf(
                'Failed to create closure from callable: class `%s` does not exist or does not implement `%s`',
                is_object($callable) ? $callable::class : $callable,
                $invoke
            )
        );
    }

    if (Invokable::returnsClosure($callable, $invoke)) {
        return Invokable::resolve($callable, $resolve)->$invoke();
    }

    return Closure::fromCallable(
        function () use ($callable, $resolve, $invoke) {
            static $resolved;
            $resolved ??= Invokable::resolve($callable, $resolve);
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
        && !Invokable::isInvokable($callable, $invoke)
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
    return Invokable::isInvokable($object, $invoke);
}
