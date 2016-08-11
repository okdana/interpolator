# Dana\Interpolator

**Interpolator** is a very simple
[string-interpolation](https://en.wikipedia.org/wiki/String_interpolation)
library for PHP. It uses a Ruby-style syntax and supports a number of filters
for escaping interpolated values.

## Usage

```php
<?php

$interpolator = new \Dana\Interpolator\Interpolator();

// Basic usage: Both of these print 'Hello, dana!'
echo $interpolator->render('Hello, %{0}!', ['dana']), "\n";
echo $interpolator->render('Hello, %{name}!', ['name' => 'dana']), "\n";

// Filter usage: This prints 'Hello, DANA!' (U = upper-case)
echo $interpolator->render('Hello, %{0|U}!', ['dana']), "\n";

// Filter usage: This prints 'Hello, "DANA"!' (U = upper-case, j = JSON-encode)
echo $interpolator->render('Hello, %{0|Uj}!', ['dana']), "\n";
```

**Interpolator**'s `render()` method is its most useful — it performs the actual
interpolation and then returns the result. `render()` takes two arguments: the
string to be interpolated, and an array of fixtures (values to inject into the
string). When a place-holder (`%{foo}`) is encountered, that place-holder name
(`foo`) will be looked up in the keys of the fixtures array, and the associated
value will be inserted into the string where the place-holder used to be.

Each place-holder may optionally be suffixed by a `|` and one or more filter
specifiers. Most of the available filters are used for escaping — for example,
the `e` filter specifier passes the fixture value through `escapeshellarg()`
before rendering it, `h` passes it through `htmlspecialchars()`, and so on.
There are also filters for case conversion (`L`, `U`) and white-space
manipulation (`t`, `w`, `W`). Filters may be chained together — for example,
`Lhj` will convert the value to lower-case, then pass it through
`htmlspecialchars()`, then pass it through `json_encode()` (in that exact
order).

To escape a place-holder in a string, simply prefix it by a `\`: `\%{0}`

By default, when **Interpolator** encounters a missing fixture or a fixture that
can't be converted to a string, it throws an exception. You can disable this
'strict' behaviour by passing an option to the constructor:

```php
$interpolator = new Interpolator(['strict' => false]);
```

Note that the presence of an illegal filter specifier will continue to raise an
exception even when strict mode is disabled.

## To do

* Better documentation
* Tests for option methods
* Maybe some way to set default filters?

