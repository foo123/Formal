Formal
======

A simple and versatile input data validation framework based on built-in and custom rules for PHP, JavaScript, Python

version **1.0.0**

![Formal](/formal.jpg)


### Contents

1. [Example](#example)
2. [API Reference](#api-reference)


### Example

```html
<form method="post">
Foo: <input name="foo" type="text" value="" />

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
        ->option('defaults', [
            'foo' => 'bar'
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

// fields typecaster
Formal::typecast('fields', ['field1' => $typecaster1, 'field2' => $typecaster2, ..]);

// default value typecaster
Formal::typecast('default', $defaultValue);

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
Formal::typecast(function($val, $key, $formalInstance) {
    // typecast and return new $val
    return $val;
}, $args = null);
```

**Validators**

```php
// optional validator, only if value is not missing
Formal::validate('optional', $requiredValidator);

// fields validator
Formal::validate('fields', ['field1' => $validator1, 'field2' => $validator2, ..], $errMsg = null);

// is numeric validator
Formal::validate('numeric', $args = null, $errMsg = null);

// is object validator
Formal::validate('object', $args = null, $errMsg = null);

// is array validator
Formal::validate('array', $args = null, $errMsg = null);

// is file validator
Formal::validate('file');

// mime-type validator
Formal::validate('mimetype', ['type1', 'type2', ..], $errMsg = null);

// is empty validator
Formal::validate('empty', $args = null, $errMsg = null);

// max items validator
Formal::validate('maxcount', $maxCount, $errMsg = null);

// min items validator
Formal::validate('mincount', $minCount, $errMsg = null);

// max chars validator
Formal::validate('maxlen', $maxLen, $errMsg = null);

// min chars validator
Formal::validate('minlen', $minLen, $errMsg = null);

// max file size validator
Formal::validate('maxsize', $maxSize, $errMsg = null);

// min file size validator
Formal::validate('minsize', $minSize, $errMsg = null);

// equals validator
Formal::validate('eq', $otherValueOrField, $errMsg = null);

// not equals validator
Formal::validate('neq', $otherValueOrField, $errMsg = null);

// greater than validator
Formal::validate('gt', $otherValueOrField, $errMsg = null);

// greater than or equal validator
Formal::validate('gte', $otherValueOrField, $errMsg = null);

// less than validator
Formal::validate('lt', $otherValueOrField, $errMsg = null);

// less than or equal validator
Formal::validate('lte', $otherValueOrField, $errMsg = null);

// between values (included) validator
Formal::validate('between', [$minValueOrField, $maxValueOrField], $errMsg = null);

// in array of values validator
Formal::validate('in', [$val1, $val2, ..], $errMsg = null);

// not in array of values validator
Formal::validate('not_in', [$val1, $val2, ..], $errMsg = null);

// match pattern validator
Formal::validate('match', $pattern, $errMsg = null);

// match valid email pattern validator
Formal::validate('email', $args = null, $errMsg = null);

// match valid url pattern validator
Formal::validate('url', $args = null, $errMsg = null);

// not validator
$validator->_not_();

// $validator1 and $validator2
$validator1->_and_($validator2);

// $validator1 or $validator2
$validator1->_or_($validator2);

// custom validator
Formal::validate(function($val, $key, $formalInstance, $missingValue) {
    // validate and return true or false
    // optionally you can throw FormalException with custom error message
    throw new FormalException('my custom error message');
    return false;
}, $args = null, $errMsg = null);
```

