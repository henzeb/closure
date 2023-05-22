<?php

namespace Henzeb\Closure\Tests\Unit;

use Closure;
use Exception;
use Henzeb\Closure\Tests\Stubs\HelloWorld;
use PHPUnit\Framework\TestCase;
use TypeError;
use function Henzeb\Closure\bind;
use function Henzeb\Closure\binding;
use function Henzeb\Closure\call;
use function Henzeb\Closure\closure;
use function Henzeb\Closure\invokable;

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

    public function testMemoizationOfResolved()
    {
        $class = new class {
            private int $count = 0;

            public function __invoke(): int
            {
                return ++$this->count;
            }
        };
        $closure = closure($class::class);
        $this->assertEquals(1, call($closure));
        $this->assertEquals(2, call($closure));
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
                if ($throw) {
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

    public function testBind()
    {
        $class = new class {
            private string $hello = 'hello world';
        };

        $this->assertSame(
            'hello world',
            bind(fn() => $this->hello, $class)()
        );
    }

    public function testBindInvokable()
    {
        $class = new class {
            private string $hello = 'hello world';
        };

        $invokable = new class {
            public function __invoke(): Closure
            {
                return function () {
                    return $this->hello;
                };
            }
        };

        $this->assertSame(
            'hello world',
            bind($invokable::class, $class)()
        );
    }

    public function testBindWithScope()
    {
        $class = new class extends HelloWorld {

        };

        $this->assertSame(
            'hello world',
            bind(fn() => $this->hello, $class, HelloWorld::class)()
        );
    }

    public function testCallWithoutBinding()
    {
        $class = new class {
            public function __invoke()
            {
                return 'hello world';
            }
        };

        $this->assertSame(
            'hello world',
            call($class::class)
        );

        $this->assertSame(
            'hello world',
            call($class)
        );

        $this->assertSame(
            'hello world',
            call(fn() => 'hello world')
        );
    }

    public function testCall()
    {
        $class = new class {
            private string $hello = 'hello world';
        };

        $this->assertSame(
            'hello world',
            call(fn() => $this->hello, $class)
        );
    }

    public function testCallInvokable()
    {
        $class = new class {
            private string $hello = 'hello world';
        };

        $invokable = new class {
            public function __invoke(): Closure
            {
                return function () {
                    return $this->hello;
                };
            }
        };

        $this->assertSame(
            'hello world',
            call($invokable::class, $class)
        );
    }

    public function testCallWithScope()
    {
        $class = new class extends HelloWorld {

        };

        $this->assertSame(
            'hello world',
            call(fn() => $this->hello, $class, HelloWorld::class)
        );
    }

    public function testClosureWithDifferentMethod()
    {
        $nonInvokable = new class {
            public function test(string $passable): bool
            {
                return $passable === 'hello';
            }
        };
        $closure = closure($nonInvokable::class, invoke: 'test');

        $this->assertTrue($closure('hello'));
        $this->assertFalse($closure('world'));

        $this->expectException(TypeError::class);

        closure($nonInvokable::class, invoke: 'fails');
    }

    public function testCallableObjectWithDifferentMethod()
    {
        $nonInvokable = new class {
            public function test(string $passable): bool
            {
                return $passable === 'hello';
            }
        };
        $closure = closure($nonInvokable, invoke: 'test');

        $this->assertTrue($closure('hello'));
        $this->assertFalse($closure('world'));

        $this->expectException(TypeError::class);

        closure($nonInvokable, invoke: 'fails');
    }


    public function testCallWithDifferentMethod()
    {
        $nonInvokable = new class {
            public function __invoke(): bool
            {
                return false;
            }

            public function test(): bool
            {
                return true;
            }
        };
        $result = call($nonInvokable::class, $this, invoke: 'test');

        $this->assertTrue($result);

        $this->expectException(TypeError::class);

        call($nonInvokable::class, $this, invoke: 'fails');
    }

    public function testBindWithDifferentMethod()
    {
        $nonInvokable = new class {
            public function __invoke(): bool
            {
                return false;
            }

            public function test(): bool
            {
                return true;
            }
        };
        $result = bind($nonInvokable::class, $this, invoke: 'test')();

        $this->assertTrue($result);

        $this->expectException(TypeError::class);

        bind($nonInvokable::class, $this, invoke: 'fails')();
    }

    public function testgetBinding()
    {
        $closure = function () {
        };

        $expected = new class extends HelloWorld {
        };

        $closure = $closure->bindTo($expected, HelloWorld::class);

        $binding = binding($closure);

        $this->assertSame(HelloWorld::class, $binding->getScope());
        $this->assertSame($expected, $binding->getThis());
    }

    public function testgetBindingWithInvokableClass()
    {
        $expected = new class extends HelloWorld {
        };

        $closure = new class($expected) {
            public function __construct(private $expected)
            {
            }

            public function __invoke(): Closure
            {
                return bind(
                    function () {

                    },
                    $this->expected,
                    HelloWorld::class
                );
            }
        };

        $binding = binding($closure::class, fn() => $closure);

        $this->assertSame(HelloWorld::class, $binding->getScope());
        $this->assertSame($expected, $binding->getThis());
    }

    public function testgetBindingWithNonInvokableClass()
    {
        $expected = new class extends HelloWorld {
        };

        $closure = new class($expected) {
            public function __construct(private $expected)
            {
            }

            public function test(): Closure
            {
                return bind(
                    function () {

                    },
                    $this->expected,
                    HelloWorld::class
                );
            }
        };

        $binding = binding($closure::class, fn() => $closure, 'test');

        $this->assertSame(HelloWorld::class, $binding->getScope());
        $this->assertSame($expected, $binding->getThis());

        $this->expectException(TypeError::class);

        binding($closure::class, fn() => $closure);
    }

    public function testgetBindingWithInvokableClassWithoutScope()
    {
        $expected = new class extends HelloWorld {
        };

        $closure = new class($expected) {
            public function __construct(private $expected)
            {
            }

            public function __invoke(): Closure
            {
                return (
                function () {
                }
                )->bindTo($this->expected, null);
            }
        };

        $binding = binding($closure::class, fn() => $closure);

        $this->assertSame(Closure::class, $binding->getScope());
        $this->assertSame($expected, $binding->getThis());
    }

    public function testgetBindingWithInvokableClassWithoutScopeAndThis()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $closure = new class() {
            public function __construct()
            {
            }

            public function __invoke(): Closure
            {
                return (
                function () {
                }
                )->bindTo(null, null);
            }
        };

        $binding = binding($closure::class, fn() => $closure);

        $this->assertNull($binding->getScope());
        $this->assertNull($binding->getThis());
    }

    public function testInvokable()
    {
        $class = new class {
            public function test()
            {
            }
        };
        $invokableClass = new class {
            public function __invoke()
            {
            }
        };

        $array = [];

        $this->assertTrue(invokable($invokableClass));
        $this->assertFalse(invokable($class));
        $this->assertTrue(invokable($class, 'test'));
        $this->assertFalse(invokable($array));
        $this->assertFalse(invokable(STDIN));
        $this->assertFalse(invokable(null));
        $this->assertFalse(invokable(5));
        $this->assertFalse(invokable(true));
        $this->assertFalse(invokable(false));
        $this->assertFalse(
            invokable(
                'random-not-existing-closure-string'
            )
        );
    }
}
