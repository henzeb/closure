<?php

namespace Henzeb\Closure\Tests\Unit\Support;

use Closure;
use Henzeb\Closure\Support\ClosureBinding;
use Henzeb\Closure\Tests\Stubs\HelloWorld;
use PHPUnit\Framework\TestCase;

use function Henzeb\Closure\binding;

class ClosureBindingTest extends TestCase
{
    public function testGetScopeWithoutBinding()
    {
        $closure = function () {
        };

        $binding = new ClosureBinding(
            $closure
        );

        $this->assertSame(self::class, $binding->getScope());

        $this->assertSame($this, $binding->getThis());
    }

    public function testGetScopeBoundToNull()
    {
        $closure = function () {
        };

        $binding = new ClosureBinding(
            $closure->bindTo(null, null)
        );

        $this->assertNull($binding->getScope());

        $this->assertNull($binding->getThis());
    }

    public function testGetScopeBoundToClassWithoutScope()
    {
        $closure = function () {
        };

        $expected = new HelloWorld();

        $binding = new ClosureBinding(
            $closure->bindTo($expected, null)
        );

        $this->assertSame(Closure::class, $binding->getScope());

        $this->assertSame($expected, $binding->getThis());
    }

    public function testGetScopeBoundToClassWithSameScope()
    {
        $closure = function () {
        };

        $expected = new HelloWorld();

        $binding = new ClosureBinding(
            $closure->bindTo($expected, $expected)
        );

        $this->assertSame($expected::class, $binding->getScope());

        $this->assertSame($expected, $binding->getThis());
    }

    public function testGetScopeBoundToClassWithDifferentScope()
    {
        $closure = function () {
        };

        $expected = new class extends HelloWorld {
        };

        $binding = new ClosureBinding(
            $closure->bindTo($expected, HelloWorld::class)
        );

        $this->assertSame(HelloWorld::class, $binding->getScope());

        $this->assertSame($expected, $binding->getThis());
    }

    public function testDebugInfo()
    {
        $closure = function () {
        };

        $expected = new class extends HelloWorld {
        };

        $binding = new ClosureBinding(
            $closure->bindTo($expected, HelloWorld::class)
        );

        $this->assertSame([
            'scope' => HelloWorld::class,
            'this' => $expected
        ], $binding->__debugInfo());
    }

    public function testDebugInfoWithVariables()
    {
        $thisVariable = $this;
        $closure = function () use ($thisVariable) {
        };

        $expected = new class extends HelloWorld {
        };

        $binding = new ClosureBinding(
            $closure->bindTo($expected, HelloWorld::class)
        );

        $this->assertSame(
            [
                'scope' => HelloWorld::class,
                'this' => $expected,
                'variables' => [
                    'thisVariable' => $thisVariable
                ]
            ],
            $binding->__debugInfo()
        );
    }

    public function testDebugInfoWithStatic()
    {
        $thisVariable = $this;
        $closure = function () use ($thisVariable) {
            static $staticVar;
            $staticVar = $thisVariable;
        };

        $binding = new ClosureBinding(
            $closure
        );

        $this->assertSame(
            [
                'scope' => $this::class,
                'this' => $this,
                'variables' => [
                    'thisVariable' => $thisVariable,
                    'staticVar' => null,
                ]
            ],
            $binding->__debugInfo()
        );

        $closure();

        $this->assertSame(
            [
                'scope' => $this::class,
                'this' => $this,
                'variables' => [
                    'thisVariable' => $thisVariable,
                    'staticVar' => $thisVariable,
                ]
            ],
            $binding->__debugInfo()
        );
    }

    public function testDebugInfoWithUseOnNonBindedClosure()
    {
        $thisVariable = $this;
        $closure = function () use ($thisVariable) {
        };

        $binding = new ClosureBinding(
            $closure
        );

        $this->assertSame(
            [
                'scope' => $this::class,
                'this' => $this,
                'variables' => [
                    'thisVariable' => $thisVariable
                ]
            ],
            $binding->__debugInfo()
        );
    }

    public function testGetUse()
    {
        $thisVariable = $this;
        $closure = function () use ($thisVariable) {
        };

        $this->assertSame(
            $thisVariable,
            binding($closure)->get('thisVariable')
        );
    }

    public function testGetNonExistentUse()
    {
        $closure = function () {
        };

        $this->assertNull(
            binding($closure)->get('thisVariable')
        );
    }
}
