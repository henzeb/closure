<?php

namespace Henzeb\Closure\Support;

use Closure;
use ReflectionClass;
use ReflectionException;

/**
 * @internal
 */
abstract class InvokableReflection
{
    public static function getInvokeMethod(string $invoke = null): string
    {
        return $invoke ?? '__invoke';
    }

    public static function invokable(mixed $object, string $invoke = null): bool
    {
        $invoke = self::getInvokeMethod($invoke);

        return (is_object($object) || (is_string($object) && class_exists($object)))
            && method_exists($object, $invoke);
    }

    /**
     * @throws ReflectionException
     */
    public static function returnTypeIsClosure(
        string|object $object,
        string $invoke = null
    ): bool {
        if (!self::invokable($object, $invoke)) {
            return false;
        }

        $invoke = self::getInvokeMethod($invoke);

        return (new ReflectionClass($object))
                ->getMethod($invoke)
                ->getReturnType()
                ?->getName() === Closure::class;
    }
}
