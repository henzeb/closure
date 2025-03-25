<?php

use Henzeb\Closure\Support\ClosureBinding;
use Henzeb\Closure\Tests\Stubs\HelloWorld;

use function Henzeb\Closure\binding;

test('get scope without binding', function () {
    $closure = function () {
    };

    $binding = new ClosureBinding(
        $closure
    );

    expect($binding->getScope())->toBe(get_class($this));
    expect($binding->getThis())->toBe($this);
});

test('get scope bound to null', function () {
    $closure = function () {
    };

    $binding = new ClosureBinding(
        $closure->bindTo(null, null)
    );

    expect($binding->getScope())->toBeNull();
    expect($binding->getThis())->toBeNull();
});

test('get scope bound to class without scope', function () {
    $closure = function () {
    };

    $expected = new HelloWorld();

    $binding = new ClosureBinding(
        $closure->bindTo($expected, null)
    );

    expect($binding->getScope())->toBe(Closure::class);
    expect($binding->getThis())->toBe($expected);
});

test('get scope bound to class with same scope', function () {
    $closure = function () {
    };

    $expected = new HelloWorld();

    $binding = new ClosureBinding(
        $closure->bindTo($expected, $expected)
    );

    expect($binding->getScope())->toBe($expected::class);
    expect($binding->getThis())->toBe($expected);
});

test('get scope bound to class with different scope', function () {
    $closure = function () {
    };

    $expected = new class extends HelloWorld {
    };

    $binding = new ClosureBinding(
        $closure->bindTo($expected, HelloWorld::class)
    );

    expect($binding->getScope())->toBe(HelloWorld::class);
    expect($binding->getThis())->toBe($expected);
});

test('debug info', function () {
    $closure = function () {
    };

    $expected = new class extends HelloWorld {
    };

    $binding = new ClosureBinding(
        $closure->bindTo($expected, HelloWorld::class)
    );

    expect($binding->__debugInfo())->toBe([
        'scope' => HelloWorld::class,
        'this' => $expected
    ]);
});

test('debug info with variables', function () {
    $thisVariable = $this;
    $closure = function () use ($thisVariable) {
    };

    $expected = new class extends HelloWorld {
    };

    $binding = new ClosureBinding(
        $closure->bindTo($expected, HelloWorld::class)
    );

    expect($binding->__debugInfo())->toBe(
        [
            'scope' => HelloWorld::class,
            'this' => $expected,
            'variables' => [
                'thisVariable' => $thisVariable
            ]
        ]
    );
});

test('debug info with static', function () {
    $thisVariable = $this;
    $closure = function () use ($thisVariable) {
        static $staticVar;
        $staticVar = $thisVariable;
    };

    $binding = new ClosureBinding(
        $closure
    );

    expect($binding->__debugInfo())->toBe(
        [
            'scope' => get_class($this),
            'this' => $this,
            'variables' => [
                'thisVariable' => $thisVariable,
                'staticVar' => null,
            ]
        ]
    );

    $closure();

    expect($binding->__debugInfo())->toBe(
        [
            'scope' => get_class($this),
            'this' => $this,
            'variables' => [
                'thisVariable' => $thisVariable,
                'staticVar' => $thisVariable,
            ]
        ]
    );
});

test('debug info with use on non binded closure', function () {
    $thisVariable = $this;
    $closure = function () use ($thisVariable) {
    };

    $binding = new ClosureBinding(
        $closure
    );

    expect($binding->__debugInfo())->toBe(
        [
            'scope' => get_class($this),
            'this' => $this,
            'variables' => [
                'thisVariable' => $thisVariable
            ]
        ]
    );
});

test('get use', function () {
    $thisVariable = $this;
    $closure = function () use ($thisVariable) {
    };

    expect(binding($closure)->get('thisVariable'))->toBe($thisVariable);
});

test('get non existent use', function () {
    $closure = function () {
    };

    expect(binding($closure)->get('thisVariable'))->toBeNull();
});