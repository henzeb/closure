<?php

namespace Henzeb\Closure\Tests\Unit;

use Closure;
use Exception;
use Henzeb\Closure\Tests\Stubs\HelloWorld;
use PHPUnit\Framework\TestCase;
use TypeError;
use function Henzeb\Closure\bind;
use function Henzeb\Closure\call;
use function Henzeb\Closure\closure;

class ClosureTest extends TestCase
{
    public function testAnonymousFunction()
    {
        $closure = closure(
            function ($passable) {
                return $passable;
            }
        );

        $this->assertInstanceOf(Closure::class, $closure);

        $this->assertEquals('hello world', $closure('hello world'));
    }

    public function testArrowFunction()
    {
        $closure = closure(
            fn($passable) => $passable
        );

        $this->assertInstanceOf(Closure::class, $closure);

        $this->assertEquals('hello world', $closure('hello world'));
    }

    public function testClass()
    {
        $closure = closure(
            new class {
                public function __invoke($passable)
                {
                    return $passable;
                }
            }
        );

        $this->assertInstanceOf(Closure::class, $closure);

        $this->assertEquals('hello world', $closure('hello world'));
    }

    public function testFCQN()
    {
        $class = new class {
            public function __invoke($passable)
            {
                return $passable;
            }
        };

        $closure = closure(
            $class::class
        );

        $this->assertInstanceOf(Closure::class, $closure);

        $this->assertEquals('hello world', $closure('hello world'));
    }

    public function testNotInvokable()
    {
        $class = new class {
            public function invoke($passable)
            {
                return $passable;
            }
        };

        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(
            sprintf(
                'Failed to create closure from callable: class "%s" does not exist or is not invokable',
                $class::class
            )
        );

        closure(
            $class::class
        );
    }

    public function testInvalidClass()
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(
            sprintf(
                'Failed to create closure from callable: class "%s" does not exist or is not invokable',
                'ClassDoesNotExist'
            )
        );

        closure('ClassDoesNotExist');
    }

    public function testResolveUsingClosure()
    {
        $class = new class {

            public function __construct(private $passable = '')
            {
            }

            public function __invoke()
            {
                return $this->passable;
            }
        };

        $this->assertEquals(
            'hello world',
            closure(
                $class::class, fn($class) => new $class('hello world')
            )()
        );
    }

    public function testDelayedClassResolve()
    {
        $class = new class(false) {

            public function __construct(bool $throw = true)
            {
                if($throw) {
                    throw new Exception('Failed to delay construction.');
                }
            }

            public function __invoke()
            {
            }
        };

        $this->addToAssertionCount(1);

        $closure = closure($class::class);

        $this->expectException(Exception::class);
        $closure();
    }

    public function testAllowBinding()
    {
        $class = new class(false) {

            public function __construct(bool $throw = true)
            {
                if($throw) {
                    throw new Exception('Failed to delay construction.');
                }
            }

            public function __invoke()
            {
            }
        };

        $this->expectException(Exception::class);

        closure($class::class, allowBinding: true);
    }

    public function testBind()
    {
        $class = new class {
            private string $hello = 'hello world';
        };

        $this->assertSame(
            'hello world',
            bind(fn()=>$this->hello, $class)()
        );
    }

    public function testBindWithScope()
    {
        $class = new class extends HelloWorld {

        };

        $this->assertSame(
            'hello world',
            bind(fn()=>$this->hello, $class, HelloWorld::class)()
        );
    }

    public function testCall()
    {
        $class = new class {
            private string $hello = 'hello world';
        };

        $this->assertSame(
            'hello world',
            call(fn()=>$this->hello, $class)
        );
    }

    public function testCallWithScope()
    {
        $class = new class extends HelloWorld {

        };

        $this->assertSame(
            'hello world',
            call(fn()=>$this->hello, $class, HelloWorld::class)
        );
    }
}
