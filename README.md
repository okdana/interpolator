# Dana\Interpolator

**Interpolator** is a very simple
[string-interpolation](https://en.wikipedia.org/wiki/String_interpolation)
library for PHP. It uses a syntax which combines features of Ruby, Jinja/Twig,
and bash — including Jinja/Twig-style 'filters'.

## Usage

```php
$interpolator = new Interpolator();

// Basic usage: Both of these print 'Hello, dana!'
echo $interpolator->render('Hello, %{0}!', ['dana']), "\n";
echo $interpolator->render('Hello, %{name}!', ['name' => 'dana']), "\n";

// Filter usage: This prints 'Hello, DANA!' (u = upper-case)
echo $interpolator->render('Hello, %{0|u}!', ['dana']), "\n";

// Filter usage: This prints 'Hello, "DANA"!' (u = upper-case, j = JSON-encode)
echo $interpolator->render('Hello, %{0|uj}!', ['dana']), "\n";

// Filter usage: This prints 'Hello, 38095!' (c = CRC32, d = extract digits)
echo $interpolator->render('Hello, %{0|cd}!', ['dana']), "\n";
```

**Interpolator**'s `render()` method is its most useful — it performs the actual
interpolation and then returns the result. `render()` takes two arguments: the
string to be interpolated, and an array of 'fixtures' (values which might be
injected into the string). When a place-holder (`%{foo}`) is encountered, that
place-holder name (`foo`) will be looked up in the keys of the fixtures array,
and the associated value will be inserted into the string where the place-holder
used to be.

### Filters

Each place-holder may optionally be suffixed by a pipe (`|`) and one or more
single-character filter specifiers. Most of the available filters are used for
escaping and hashing — for example, the `e` filter specifier passes the fixture
value through `escapeshellarg()` before rendering it, and the `m` specifier
hashes the value with MD5. There are also filters for case conversion (`l`,
`u`), white-space manipulation (`t`, `w`, `W`), &c. Filters may be chained
together — for example, `lhj` will convert the value to lower-case, then pass it
through `htmlspecialchars()`, then pass it through `json_encode()` (in that
exact order).

This is a list of the default filter specifiers and their behaviours:

```
a  Extract letters [A-Za-z] from the string
b  Encode the string using base64
B  Encode the string using 'URL-safe' base64
c  Hash the string with CRC32
C  Hash the string with CRC32b
d  Extract digits [0–9] from the string
e  Escape the string for use as a shell argument (escapeshellarg())
f  Escape the string for use in a URL (rawurlencode()), but preserve slashes
h  Escape the string for use in HTML (htmlspecialchars())
H  Escape the string for use in HTML (htmlentities())
j  Escape the string for use in JSON (json_encode())
l  Convert the string to lower-case (mb_strtolower())
L  Convert the string to lower-case (strtolower())
m  Hash the string with MD5
p  Escape the string for use in a PCRE regular-expression pattern (preg_quote())
r  Escape the string for use in a URL (rawurlencode())
R  Escape the string for use in a URL (urlencode())
s  Hash the string with SHA1
S  Hash the string with SHA256
t  Trim the string of leading/trailing white space (trim())
u  Convert the string to upper-case (mb_strtoupper())
U  Convert the string to upper-case (strtoupper())
w  Collapse consecutive white space in the string into a single space
W  Remove all white space from the string
```

You don't have to use the defaults though — you can override some or all of them
using the `setFilter()` and `setFilters()` methods:

```php
$interpolator = new Interpolator();

// Replace all default filters by our own
$interpolator->setFilters(['a' => 'ucfirst']);

// This prints 'Foo'
echo $interpolator->render('%{0|a}', ['foo']), "\n";
```

**Interpolator** also supports auto filters — filters which are applied
automatically after all other filter processing has completed. This is
particularly useful for creating HTML templates, for example. Auto filters can
be surpressed using the special `-` filter specifier.

```php
$interpolator = new Interpolator();

// Apply htmlspecialchars() as an auto filter
$interpolator->setAutoFilters('h');

// This prints 'FOO&amp;BAR'
echo $interpolator->render('%{0|u}', ['foo&bar']), "\n";

// This prints 'FOO&BAR' (auto filters suppressed)
echo $interpolator->render('%{0|u-}', ['foo&bar']), "\n";
```

### Misc.

By default, when **Interpolator** encounters a missing fixture or a fixture that
can't be converted to a string, it throws an exception. You can disable this
'strict' behaviour by passing an option to the constructor:

```php
// Silently ignore missing/mistyped fixtures
$interpolator = new Interpolator(['strict' => false]);
```

Note that the presence of an illegal filter specifier will continue to raise an
exception even when strict mode is disabled.

## To do

* Better documentation
* More tests

## Licence

MIT.

