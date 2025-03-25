<?php

use Henzeb\Closure\Tests\Stubs\HelloWorld;

use function Henzeb\Closure\bind;
use function Henzeb\Closure\binding;
use function Henzeb\Closure\call;
use function Henzeb\Closure\closure;
use function Henzeb\Closure\invokable;
use function Henzeb\Closure\wrap;

test('anonymous function', function () {
    $closure = closure(
        function ($passable) {
            return $passable;
        }
    );

    expect($closure)->toBeInstanceOf(Closure::class);
    expect($closure('hello world'))->toBe('hello world');
});

test('arrow function', function () {
    $closure = closure(
        fn($passable) => $passable
    );

    expect($closure)->toBeInstanceOf(Closure::class);
    expect($closure('hello world'))->toBe('hello world');
});

test('class', function () {
    $closure = closure(
        new class {
            public function __invoke($passable)
            {
                return $passable;
            }
        }
    );

    expect($closure)->toBeInstanceOf(Closure::class);
    expect($closure('hello world'))->toBe('hello world');
});

test('class with returning closure', function () {
    $closure = closure(
        new class {
            public function __invoke(): Closure
            {
                return fn($passable) => $passable;
            }
        }
    );

    expect($closure)->toBeInstanceOf(Closure::class);
    expect($closure('hello world'))->toBe('hello world');
});

test('memoization of resolved', function () {
    $class = new class {
        private int $count = 0;

        public function __invoke(): int
        {
            return ++$this->count;
        }
    };
    $closure = closure($class::class);
    expect(call($closure))->toBe(1);
    expect(call($closure))->toBe(2);
});

test('FCQN', function () {
    $class = new class {
        public function __invoke($passable)
        {
            return $passable;
        }
    };

    $closure = closure(
        $class::class
    );

    expect($closure)->toBeInstanceOf(Closure::class);
    expect($closure('hello world'))->toBe('hello world');
});

test('not invokable', function () {
    $class = new class {
        public function __invoke($passable)
        {
            return $passable;
        }
    };

    expect(fn () => closure(
        $class::class,
        invoke: 'invoke'
    ))->toThrow(
        TypeError::class,
        sprintf(
            'Failed to create closure from callable: class `%s` does not exist or does not implement `invoke`',
            $class::class
        )
    );
});

test('invalid class', function () {
    expect(fn () => closure('ClassDoesNotExist'))->toThrow(
        TypeError::class,
        sprintf(
            'Failed to create closure from callable: class `%s` does not exist or does not implement `%s`',
            'ClassDoesNotExist',
            '__invoke'
        )
    );
});

test('resolve using closure', function () {
    $class = new class {
        public function __construct(private $passable = '')
        {
        }

        public function __invoke()
        {
            return $this->passable;
        }
    };

    expect(
        closure(
            $class::class, fn($class) => new $class('hello world')
        )()
    )->toBe('hello world');
});

test('delayed class resolve', function () {
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

    $closure = closure($class::class);

    expect(fn() => $closure())->toThrow(Exception::class);
});

test('bind', function () {
    $class = new class {
        private string $hello = 'hello world';
    };

    expect(
        bind(fn() => $this->hello, $class)()
    )->toBe('hello world');
});

test('bind invokable', function () {
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

    expect(
        bind($invokable::class, $class)()
    )->toBe('hello world');
});

test('bind with scope', function () {
    $class = new class extends HelloWorld {
    };

    expect(
        bind(fn() => $this->hello, $class, HelloWorld::class)()
    )->toBe('hello world');
});

test('call without binding', function () {
    $class = new class {
        public function __invoke()
        {
            return 'hello world';
        }
    };

    expect(call($class::class))->toBe('hello world');
    expect(call($class))->toBe('hello world');
    expect(call(fn() => 'hello world'))->toBe('hello world');
});

test('call', function () {
    $class = new class {
        private string $hello = 'hello world';
    };

    expect(
        call(fn() => $this->hello, $class)
    )->toBe('hello world');
});

test('call invokable', function () {
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

    expect(
        call($invokable::class, $class)
    )->toBe('hello world');
});

test('call with scope', function () {
    $class = new class extends HelloWorld {
    };

    expect(
        call(fn() => $this->hello, $class, HelloWorld::class)
    )->toBe('hello world');
});

test('closure with different method', function () {
    $nonInvokable = new class {
        public function test(string $passable): bool
        {
            return $passable === 'hello';
        }
    };
    $closure = closure($nonInvokable::class, invoke: 'test');

    expect($closure('hello'))->toBeTrue();
    expect($closure('world'))->toBeFalse();

    expect(fn() => closure($nonInvokable::class, invoke: 'fails'))->toThrow(TypeError::class);
});

test('callable object with different method', function () {
    $nonInvokable = new class {
        public function test(string $passable): bool
        {
            return $passable === 'hello';
        }
    };
    $closure = closure($nonInvokable, invoke: 'test');

    expect($closure('hello'))->toBeTrue();
    expect($closure('world'))->toBeFalse();

    expect(fn() => closure($nonInvokable, invoke: 'fails'))->toThrow(TypeError::class);
});

test('call with different method', function () {
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
    $result = call($nonInvokable::class, test(), invoke: 'test');

    expect($result)->toBeTrue();

    expect(fn() => call($nonInvokable::class, test(), invoke: 'fails'))->toThrow(TypeError::class);
});

test('bind with different method', function () {
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
    $result = bind($nonInvokable::class, test(), invoke: 'test')();

    expect($result)->toBeTrue();

    expect(fn() => bind($nonInvokable::class, test(), invoke: 'fails')())->toThrow(TypeError::class);
});

test('get binding', function () {
    $closure = function () {
    };

    $expected = new class extends HelloWorld {
    };

    $closure = $closure->bindTo($expected, HelloWorld::class);

    $binding = binding($closure);

    expect($binding->getScope())->toBe(HelloWorld::class);
    expect($binding->getThis())->toBe($expected);
});

test('get binding with invokable class', function () {
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

    expect($binding->getScope())->toBe(HelloWorld::class);
    expect($binding->getThis())->toBe($expected);
});

test('get binding with non invokable class', function () {
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

    expect($binding->getScope())->toBe(HelloWorld::class);
    expect($binding->getThis())->toBe($expected);

    expect(fn() => binding($closure::class, fn() => $closure))->toThrow(TypeError::class);
});

test('get binding with invokable class without scope', function () {
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

    expect($binding->getScope())->toBe(Closure::class);
    expect($binding->getThis())->toBe($expected);
});

test('get binding with invokable class without scope and this', function () {
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

    expect($binding->getScope())->toBeNull();
    expect($binding->getThis())->toBeNull();
});

test('invokable', function () {
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

    expect(invokable($invokableClass))->toBeTrue();
    expect(invokable($class))->toBeFalse();
    expect(invokable($class, 'test'))->toBeTrue();
    expect(invokable($array))->toBeFalse();
    expect(invokable(STDIN))->toBeFalse();
    expect(invokable(null))->toBeFalse();
    expect(invokable(5))->toBeFalse();
    expect(invokable(true))->toBeFalse();
    expect(invokable(false))->toBeFalse();
    expect(
        invokable(
            'random-not-existing-closure-string'
        )
    )->toBeFalse();
});

test('wrap', function () {
    $class = new class {
        public function test()
        {
            return 'hello world';
        }
    };

    $class2 = new class {
        public function test()
        {
            return 'hello other world';
        }
    };

    expect(wrap(true)())->toBeTrue();
    expect(wrap(false)())->toBeFalse();
    expect(wrap(STDIN)())->toBe(STDIN);
    expect(wrap($class)())->toBe($class);
    expect(wrap($class::class)())->toBe($class::class);
    expect(wrap($class::class, invoke: 'test')())->toBe('hello world');
    expect(wrap($class::class, invoke: 'test')())->toBe('hello world');
    expect(wrap($class2::class, fn() => $class, 'test')())->toBe('hello world');
});

test('closure as callable with alternative invoke', function () {
    expect(closure(fn() => 'test', invoke: 'test')())->toBe('test');
});