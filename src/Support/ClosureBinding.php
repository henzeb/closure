<?php

namespace Henzeb\Closure\Support;

use Closure;
use ReflectionException;
use ReflectionFunction;

class ClosureBinding
{
    private ReflectionFunction $reflection;

    /**
     * @throws ReflectionException
     */
    public function __construct(Closure $closure)
    {
        $this->reflection = new ReflectionFunction($closure);
    }

    public function getScope(): ?string
    {
        return $this->reflection->getClosureScopeClass()?->getName();
    }

    public function getThis(): ?object
    {
        return $this->reflection->getClosureThis();
    }

    public function get(string $variable): mixed
    {
        return $this->reflection->getStaticVariables()[$variable] ?? null;
    }

    public function __debugInfo(): ?array
    {
        return array_filter(
            [
                'scope' => $this->getScope(),
                'this' => $this->getThis(),
                'variables' => $this->reflection->getStaticVariables()
            ]
        );
    }
}
