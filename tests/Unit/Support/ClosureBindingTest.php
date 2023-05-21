<?php

namespace Henzeb\Closure\Tests\Unit\Support;

use Closure;
use Henzeb\Closure\Support\ClosureBinding;
use Henzeb\Closure\Tests\Stubs\HelloWorld;
use PHPUnit\Framework\TestCase;

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
}
