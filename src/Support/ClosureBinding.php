<?php

namespace Henzeb\Closure\Support;

use Closure;
use ReflectionFunction;

class ClosureBinding
{
    private ReflectionFunction $reflection;

    public function __construct(Closure $closure)
    {
        $this->reflection = new ReflectionFunction($closure);
    }

    public function getScope(): ?string
    {
        $scope = $this->reflection->getClosureScopeClass()?->getName();

        if ($scope) {
            return $scope;
        }

        return $this->reflection->getStaticVariables()['newScope']
            ?? null;
    }

    public function getThis(): ?object
    {
        $object = $this->reflection->getClosureThis();

        if ($object) {
            return $object;
        }

        return $this->reflection->getStaticVariables()['newThis']
            ?? null;
    }
}
