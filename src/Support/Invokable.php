<?php

namespace Henzeb\Closure\Support;

use Closure;
use ReflectionClass;
use ReflectionException;

/**
 * @internal
 */
abstract class Invokable
{
    /**
     * @var callable|null
     */
    private static $resolver;

    public static function getInvokeMethod(?string $invoke = null): string
    {
        return $invoke ?? '__invoke';
    }

    public static function isInvokable(mixed $object, ?string $invoke = null): bool
    {
        $invoke = self::getInvokeMethod($invoke);

        return (is_object($object) || (is_string($object) && class_exists($object)))
            && method_exists($object, $invoke);
    }

    /**
     * @throws ReflectionException
     */
    public static function returnsClosure(
        string|object $object,
        ?string       $invoke = null
    ): bool
    {
        if (!self::isInvokable($object, $invoke)) {
            return false;
        }

        $invoke = self::getInvokeMethod($invoke);

        return (new ReflectionClass($object))
                ->getMethod($invoke)
                ->getReturnType()
                ?->getName() === Closure::class;
    }

    public static function resolve(mixed $callable, ?callable $resolve): object
    {
        if (is_object($callable)) {
            return $callable;
        }

        if ($resolve) {
            return $resolve($callable);
        }

        if (self::$resolver) {
            return (self::$resolver)($callable);
        }

        return new $callable();
    }

    public static function setResolver(?callable $resolver): void
    {
        self::$resolver = $resolver;
    }
}
