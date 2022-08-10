Formal
======

A simple and versatile (form) input data validation framework based on built-in and custom rules for PHP, JavaScript, Python

version **1.1.0**

![Formal](/formal.jpg)

**see also:**

* [ModelView](https://github.com/foo123/modelview.js) a simple, fast, powerful and flexible MVVM framework for JavaScript
* [tico](https://github.com/foo123/tico) a tiny, super-simple MVC framework for PHP
* [LoginManager](https://github.com/foo123/LoginManager) a simple, barebones agnostic login manager for PHP, JavaScript, Python
* [SimpleCaptcha](https://github.com/foo123/simple-captcha) a simple, image-based, mathematical captcha with increasing levels of difficulty for PHP, JavaScript, Python
* [Dromeo](https://github.com/foo123/Dromeo) a flexible, and powerful agnostic router for PHP, JavaScript, Python
* [PublishSubscribe](https://github.com/foo123/PublishSubscribe) a simple and flexible publish-subscribe pattern implementation for PHP, JavaScript, Python
* [Importer](https://github.com/foo123/Importer) simple class &amp; dependency manager and loader for PHP, JavaScript, Python
* [Contemplate](https://github.com/foo123/Contemplate) a fast and versatile isomorphic template engine for PHP, JavaScript, Python
* [HtmlWidget](https://github.com/foo123/HtmlWidget) html widgets, made as simple as possible, both client and server, both desktop and mobile, can be used as (template) plugins and/or standalone for PHP, JavaScript, Python (can be used as [plugins for Contemplate](https://github.com/foo123/Contemplate/blob/master/src/js/plugins/plugins.txt))
* [Paginator](https://github.com/foo123/Paginator)  simple and flexible pagination controls generator for PHP, JavaScript, Python
* [Formal](https://github.com/foo123/Formal) a simple and versatile (Form) Data validation framework based on Rules for PHP, JavaScript, Python
* [Dialect](https://github.com/foo123/Dialect) a cross-vendor &amp; cross-platform SQL Query Builder, based on [GrammarTemplate](https://github.com/foo123/GrammarTemplate), for PHP, JavaScript, Python
* [DialectORM](https://github.com/foo123/DialectORM) an Object-Relational-Mapper (ORM) and Object-Document-Mapper (ODM), based on [Dialect](https://github.com/foo123/Dialect), for PHP, JavaScript, Python
* [Unicache](https://github.com/foo123/Unicache) a simple and flexible agnostic caching framework, supporting various platforms, for PHP, JavaScript, Python
* [Xpresion](https://github.com/foo123/Xpresion) a simple and flexible eXpression parser engine (with custom functions and variables support), based on [GrammarTemplate](https://github.com/foo123/GrammarTemplate), for PHP, JavaScript, Python
* [Regex Analyzer/Composer](https://github.com/foo123/RegexAnalyzer) Regular Expression Analyzer and Composer for PHP, JavaScript, Python


### Contents

1. [Example](#example)
2. [API Reference](#api-reference)


### Example

```html
<form method="post">
Foo: <input name="foo" type="text" value="" />

Moo:
<input name="moo[0][choo]" type="text" value="1" />
<input name="moo[1][choo]" type="text" value="2" />
<input name="moo[2][choo]" type="text" value="3" />

Koo:
<input name="koo[]" type="text" value="" />
<input name="koo[]" type="text" value="" />
<input name="koo[]" type="text" value="" />

Nums:
<input name="num[]" type="text" value="0.1" />
<input name="num[]" type="text" value="1.2" />

Dates:
<input name="date[]" type="text" value="2012-11-02" />
<input name="date[]" type="text" value="20-11-02" />

<button type="submit">Submit</button>
</form>
```

```php
$formal = (new Formal())
        ->option('WILDCARD', '*') // default
        ->option('SEPARATOR', '.') // default
        ->option('break_on_first_error', false) // default
        ->option('defaults', [
            'foo' => 'bar',
            'moo.*.foo' => 'bar',
            'koo.*' => 'bar'
        ])
        ->option('typecasters', [
            'num.*' => Formal::typecast('composite', [Formal::typecast('float'), Formal::typecast('clamp', [0.0, 1.0])
        ])])
        ->option('validators', [
            'date.*' => Formal::validate('match', Formal::datetime('Y-m-d'), '"{key}" should match {args} !'),
            'date.0' => Formal::validate('eq', Formal::field('date.1'))
        ])
;
$data = $formal->process($_POST);
$err = $formal->getErrors();

print_r($data);

echo implode("\n", $err) . PHP_EOL;

echo $err[0]->getMsg() . PHP_EOL;
echo implode('.', $err[0]->getKey()) . PHP_EOL;

/* output

Array
(
    [foo] => bar
    [moo] => Array
        (
            [0] => Array
                (
                    [choo] => 1
                    [foo] => bar
                )

            [1] => Array
                (
                    [choo] => 2
                    [foo] => bar
                )

            [2] => Array
                (
                    [choo] => 3
                    [foo] => bar
                )

        )

    [koo] => Array
        (
            [0] => bar
            [1] => bar
            [2] => bar
        )

    [num] => Array
        (
            [0] => 0.1
            [1] => 1
        )

    [date] => Array
        (
            [0] => 2012-11-02
            [1] => 20-11-02
        )

)
"date.1" should match Y-m-d !
"date.0" must be equal to "date.1"!

"date.1" should match Y-m-d !
date.1
*/
```

### API Reference

**Typecasters**

```php
// composite typecaster
Formal::typecast('composite', [$typecaster1, $typecaster2, ..]);

// boolean typecaster
Formal::typecast('bool');

// int typecaster
Formal::typecast('int');

// float typecaster
Formal::typecast('float');

// string typecaster
Formal::typecast('str');

// min value typecaster
Formal::typecast('min', $minValue);

// max value typecaster
Formal::typecast('max', $maxValue);

// clamp typecaster
Formal::typecast('clamp', [$minValue, $maxValue]);

// trim typecaster
Formal::typecast('trim');

// lowercase typecaster
Formal::typecast('lower');

// uppercase typecaster
Formal::typecast('upper');

// custom typecaster
Formal::typecast(function($val, $args, $key, $formalInstance) {
    // typecast and return new $val
    return $val;
}, $args = null);
```

**Validators**

```php
// optional validator, only if value is not missing
Formal::validate('optional', $requiredValidator);

// required validator, fails if value is missing or null
Formal::validate('required');

// is numeric validator
Formal::validate('numeric');

// is object validator
Formal::validate('object');

// is array validator
Formal::validate('array';

// is file validator
Formal::validate('file');

// is empty validator
Formal::validate('empty');

// max items validator
Formal::validate('maxitems', $maxCount);

// min items validator
Formal::validate('minitems', $minCount);

// max chars validator
Formal::validate('maxchars', $maxLen);

// min chars validator
Formal::validate('minchars', $minLen);

// max file size validator
Formal::validate('maxsize', $maxSize);

// min file size validator
Formal::validate('minsize', $minSize);

// equals validator
Formal::validate('eq', $otherValueOrField);

// not equals validator
Formal::validate('neq', $otherValueOrField);

// greater than validator
Formal::validate('gt', $otherValueOrField);

// greater than or equal validator
Formal::validate('gte', $otherValueOrField);

// less than validator
Formal::validate('lt', $otherValueOrField);

// less than or equal validator
Formal::validate('lte', $otherValueOrField);

// between values (included) validator
Formal::validate('between', [$minValueOrField, $maxValueOrField]);

// in array of values validator
Formal::validate('in', [$val1, $val2, ..]);

// not in array of values validator
Formal::validate('not_in', [$val1, $val2, ..]);

// match pattern validator
Formal::validate('match', $pattern);

// match valid email pattern validator
Formal::validate('email');

// match valid url pattern validator
Formal::validate('url');

// not validator
$validator->_not_($errMsg = null);

// $validator1 and $validator2
$validator1->_and_($validator2);

// $validator1 or $validator2
$validator1->_or_($validator2);

// custom validator
Formal::validate(function($val, $args, $key, $formalInstance, $missingValue, $errMsg) {
    // validate and return true or false
    // optionally you can throw FormalException with custom error message
    throw new FormalException('my custom error message');
    return false;
}, $args = null, $errMsg = null);
```

