# Closure

[![Build Status](https://github.com/henzeb/closure/workflows/tests/badge.svg)](https://github.com/henzeb/closure/actions)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/henzeb/closure.svg?style=flat-square)](https://packagist.org/packages/henzeb/closure)
[![Total Downloads](https://img.shields.io/packagist/dt/henzeb/closure.svg?style=flat-square)](https://packagist.org/packages/henzeb/closure)
[![License](https://img.shields.io/packagist/l/henzeb/closure)](https://packagist.org/packages/henzeb/closure)

In PHP, whe have `Closure::fromCallable()`. Sadly, it does not work
with FQCN strings.

This package ships a function that extends fromCallable allowing
FQCN strings of invokable classes to become closures.

## Installation

Just install with the following command.

```bash
composer require henzeb/closure
```

## usage

````php
use function Henzeb\Closure\closure;

class InvokableClass
{
    public function __invoke() {
        print 'Hello World!';
    }
}

class ReturnsClosures
{
    public function __invoke(): Closure {
        print fn()=>'Hello World!';
    }
}

class NotInvokableClass
{
    public function invoke() {
        print 'Hello World!';
    }
}

function helloWorld()
{
    print 'Hello World!';
}

closure(Invokable::class)(); // prints Hello World!
closure(new InvokableClass)(); // prints Hello World!

closure(ReturnsClosure::class)(); // prints Hello World!
closure(new ReturnsClosure())(); // prints Hello World!

closure('helloWorld')(); // prints Hello World!

closure(
    function(){
        print 'Hello World!'
    }
); // prints Hello World!;

closure(NotInvokable::class); // throws TypeError
````

### Resolving

Sometimes you may need to tell `closure` how to resolve your callable

````php
use function Henzeb\Closure\closure;

class InvokableClass
{
    public function __construct(private $message)
    {
    }

    public function __invoke() {
        print $this->message;
    }
}

closure(
    Invokable::class,
    fn(string $class) => $class('Hello World!')
); // prints Hello World!
````

Note: While `closure` may throw a TypeError on creation, resolving of
FQCN happens on actually calling the newly created closure. Resolving
happens only the first time. Except when the invokable method returns
a Closure, then the resolving takes place first to return this closure.

### Binding

Closures are adorable hacky little creatures due to their ability
to bind and change scopes. If you ever have that need
you can use `bind`.

````php
use function Henzeb\Closure\bind;

bind(
    fn()=> // do what you need
    , $anyThis
)('your Arg');
````

To use a FCQN or invokable class with binding, the `__invoke` function must
return a Closure. Please note the return type! Without, it wil fail.

````php
class InvokableClass {
    public function __invoke($hello): Closure {
        return $anyThis->hello($hello);
    }
}
````

````php
use function Henzeb\Closure\bind;

bind(InvokableClass::class, $anyThis)('your Arg');
bind(InvokableClass::class, $anyThis, AnyScope::class)('your Arg');

bind(new InvokableClass, $anyThis)('your Arg');
````

Bind will under the hood use `closure` to get a closure, and then
binds the new `this` and scope to it. It then returns a Closure you can use.

## Calling

You can also directly call a (binded) closure using `call`.

````php
use function Henzeb\Closure\call;

call(
    fn()=>'Hello world!'
); // returns Hello World!

call(
    InvokableClass::class
); // returns whatever your callable returns

call(InvokableClass::class, $anyThis); // returns whatever your callable returns
call(
    InvokableClass::class,
    $anyThis,
    AnyScope::class
); // returns whatever your callable returns
call(new InvokableClass, $anyThis);  // returns whatever your callable returns

call(
    InvokableClass::class,
    $anyThis,
    resolve: fn($class) => new $class('Hello World!')
); // returns whatever your callable returns

call(
    fn()=>'Hello world!'
    , $anyThis
); // returns Hello World!
````

## Invoking different methods

In some cases you'd like to wrap a non-callable FQCN or class.
Each function accepts a parameter named `invoke`.

````php
class NonInvokable {
    public function hello()
    {
        print 'Hello World!';
    }
}
````

````php
use function Henzeb\Closure\closure;
use function Henzeb\Closure\bind;
use function Henzeb\Closure\call;

closure(NonInvokable::class, invoke: 'hello')(); // prints Hello World!;
bind(NonInvokable::class, $newthis, invoke: 'hello')(); // prints Hello World!;
call(NonInvokable::class, $newthis, invoke: 'hello'); // prints Hello World!;

````

## invokable

To test an object is invokable, and thus can become a closure.
The function accepts any value.

````php
use function Henzeb\Closure\invokable;

invokable(NonInvokable::class); // returns false
invokable(NonInvokable::class, 'hello'); // returns true

invokable(InvokableClass::class); // returns true
invokable([]); // returns false
invokable(STDIN); // returns false

````

## Wrapping

Sometimes you may expect a boolean or a callable. using `closure`,
This would fail. Using `wrap`, anything that's not invokable, will
be wrapped inside a closure.

````php
use function Henzeb\Closure\wrap;

wrap(NonInvokable::class)(); // returns NonInvokable instance

wrap(true)(); // returns true
wrap(false)(); // returns true

wrap(InvokableClass::class)(); // returns what __invoke would return

wrap(
    InvokableClass::class,
    invoke: 'invokableMethod'
)(); // returns what invokableMethod would return

wrap([])(); // returns an empty array
wrap(STDIN)(); // returns the STDIN stream

````

## Bindings

In some cases you might want to know the current binding of a
closure or invokable.

````php
use function Henzeb\Closure\binding;

binding(function(){})->getScope(); // returns $newScope value
binding(function(){})->getThis(); // returns $newThis value

````

### accessing static variables

When you have closures that uses the `use` clause or when you
use static variables inside your closure you can access them
with the following:

````php
use function Henzeb\Closure\binding;

$myVariable = 'Hello World!';

$closure = function() use ($myVariable) {
    static $myStaticVariable;

    $myStaticVariable = 'Hello World!'
}

binding($closure)->get('myVariable'); // returns Hello World!
binding($closure)->get('myOtherVariable'); // returns null.

binding($closure)->get('myStaticVariable'); // returns null.

$closure(); //calling the closure

binding($closure)->get('myStaticVariable'); // returns Hello World!


````

### debug info

You can use `var_dump` to print a list of all variables.

````php
use function Henzeb\Closure\binding;

var_dump(binding(fn()=>true));
````

This will return an array with the current `scope`,
the current `this` and any static variables associated with it.

## Testing this package

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed
recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email
henzeberkheij@gmail.com instead of using the issue tracker.

## Credits

- [Henze Berkheij](https://github.com/henzeb)

## License

The GNU AGPLv. Please see [License File](LICENSE.md) for more information.
