<?php
/**
*   Formal
*   validate nested (form) data with built-in and custom rules for PHP, JavaScript, Python
*
*   @version 1.1.1
*   https://github.com/foo123/Formal
*
**/
if (!class_exists("Formal", false))
{
class FormalException extends Exception
{
    public function __construct($message = "", $code = 1, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class FormalField
{
    public $field = null;

    public static function _($field)
    {
        return new static($field);
    }

    public function __construct($field)
    {
        $this->field = $field;
    }

    public function __destruct()
    {
        $this->field = null;
    }
}

class FormalDateTime
{
    public static function by_length_desc($a, $b)
    {
        return strlen($b)-strlen($a);
    }

    public static function esc_re($s)
    {
        return preg_quote($s, '/');
    }

    public static function get_alternate_pattern($alts)
    {
        usort($alts, array(__CLASS__, 'by_length_desc'));
        return implode('|', array_map(array(__CLASS__, 'esc_re'), $alts));
    }

    public static function _($format, $locale = null)
    {
        return new static($format, $locale);
    }

    private $format = '';
    private $pattern = '';

    public function __construct($format, $locale = null)
    {
        if (empty($locale)) $locale = array(
            'day_short' => array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'),
            'day' => array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
            'month_short' => array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'),
            'month' => array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'),
            'meridian' => array('am' => 'am', 'pm' => 'pm', 'AM' => 'AM', 'PM' => 'PM'),
            'timezone_short' => array('UTC'),
            'timezone' => array('UTC'),
            'ordinal' => array('ord' => array('1' => 'st', '2' => 'nd', '3' => 'rd'), 'nth' => 'th'),
        );

        // (php) date formats
        // http://php.net/manual/en/function.date.php
        $D = array(
            // Day --
            // Day of month w/leading 0; 01..31
             'd'=> '(31|30|29|28|27|26|25|24|23|22|21|20|19|18|17|16|15|14|13|12|11|10|09|08|07|06|05|04|03|02|01)'
            // Shorthand day name; Mon...Sun
            ,'D'=> '(' . self::get_alternate_pattern($locale['day_short']) . ')'
            // Day of month; 1..31
            ,'j'=> '(31|30|29|28|27|26|25|24|23|22|21|20|19|18|17|16|15|14|13|12|11|10|9|8|7|6|5|4|3|2|1)'
            // Full day name; Monday...Sunday
            ,'l'=> '(' . self::get_alternate_pattern($locale['day']) . ')'
            // ISO-8601 day of week; 1[Mon]..7[Sun]
            ,'N'=> '([1-7])'
            // Ordinal suffix for day of month; st, nd, rd, th
            ,'S'=> '' // added below
            // Day of week; 0[Sun]..6[Sat]
            ,'w'=> '([0-6])'
            // Day of year; 0..365
            ,'z'=> '([1-3]?[0-9]{1,2})'

            // Week --
            // ISO-8601 week number
            ,'W'=> '([0-5]?[0-9])'

            // Month --
            // Full month name; January...December
            ,'F'=> '(' . self::get_alternate_pattern($locale['month']) . ')'
            // Month w/leading 0; 01...12
            ,'m'=> '(12|11|10|09|08|07|06|05|04|03|02|01)'
            // Shorthand month name; Jan...Dec
            ,'M'=> '(' . self::get_alternate_pattern($locale['month_short']) . ')'
            // Month; 1...12
            ,'n'=> '(12|11|10|9|8|7|6|5|4|3|2|1)'
            // Days in month; 28...31
            ,'t'=> '(31|30|29|28)'

            // Year --
            // Is leap year?; 0 or 1
            ,'L'=> '([01])'
            // ISO-8601 year
            ,'o'=> '(\\d{2,4})'
            // Full year; e.g. 1980...2010
            ,'Y'=> '([12][0-9]{3})'
            // Last two digits of year; 00...99
            ,'y'=> '([0-9]{2})'

            // Time --
            // am or pm
            ,'a'=> '(' . self::get_alternate_pattern(array(
                $locale['meridian']['am'],
                $locale['meridian']['pm']
            )) . ')'
            // AM or PM
            ,'A'=> '(' . self::get_alternate_pattern(array(
                $locale['meridian']['AM'],
                $locale['meridian']['PM']
            )) . ')'
            // Swatch Internet time; 000..999
            ,'B'=> '([0-9]{3})'
            // 12-Hours; 1..12
            ,'g'=> '(12|11|10|9|8|7|6|5|4|3|2|1)'
            // 24-Hours; 0..23
            ,'G'=> '(23|22|21|20|19|18|17|16|15|14|13|12|11|10|9|8|7|6|5|4|3|2|1|0)'
            // 12-Hours w/leading 0; 01..12
            ,'h'=> '(12|11|10|09|08|07|06|05|04|03|02|01)'
            // 24-Hours w/leading 0; 00..23
            ,'H'=> '(23|22|21|20|19|18|17|16|15|14|13|12|11|10|09|08|07|06|05|04|03|02|01|00)'
            // Minutes w/leading 0; 00..59
            ,'i'=> '([0-5][0-9])'
            // Seconds w/leading 0; 00..59
            ,'s'=> '([0-5][0-9])'
            // Microseconds; 000000-999000
            ,'u'=> '([0-9]{6})'

            // Timezone --
            // Timezone identifier; e.g. Atlantic/Azores, ...
            ,'e'=> '(' . self::get_alternate_pattern($locale['timezone']) . ')'
            // DST observed?; 0 or 1
            ,'I'=> '([01])'
            // Difference to GMT in hour format; e.g. +0200
            ,'O'=> '([+-][0-9]{4})'
            // Difference to GMT w/colon; e.g. +02:00
            ,'P'=> '([+-][0-9]{2}:[0-9]{2})'
            // Timezone abbreviation; e.g. EST, MDT, ...
            ,'T'=> '(' . self::get_alternate_pattern($locale['timezone_short']) . ')'
            // Timezone offset in seconds (-43200...50400)
            ,'Z'=> '(-?[0-9]{5})'

            // Full Date/Time --
            // Seconds since UNIX epoch
            ,'U'=> '([0-9]{1,8})'
            // ISO-8601 date. Y-m-d\\TH:i:sP
            ,'c'=> '' // added below
            // RFC 2822 D, d M Y H:i:s O
            ,'r'=> '' // added below
        );
        // Ordinal suffix for day of month; st, nd, rd, th
        $lords = array_values($locale['ordinal']['ord']);
        $lords[] = $locale['ordinal']['nth'];
        $D['S'] = '(' . self::get_alternate_pattern($lords) . ')';
        // ISO-8601 date. Y-m-d\\TH:i:sP
        $D['c'] = $D['Y'].'-'.$D['m'].'-'.$D['d'].'\\\\'.$D['T'].$D['H'].':'.$D['i'].':'.$D['s'].$D['P'];
        // RFC 2822 D, d M Y H:i:s O
        $D['r'] = $D['D'].',\\s'.$D['d'].'\\s'.$D['M'].'\\s'.$D['Y'].'\\s'.$D['H'].':'.$D['i'].':'.$D['s'].'\\s'.$D['O'];

        $re = '';
        $l = strlen($format);
        for ($i=0; $i<$l; ++$i)
        {
            $f = $format[$i];
            $re .= isset($D[$f]) ? $D[ $f ] : self::esc_re($f);
        }


        $this->format = $format;
        $this->pattern = '/^' . $re . '$/';
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function getPattern()
    {
        return $this->pattern;
    }

    public function __toString()
    {
        return $this->pattern;
    }
}

class FormalType
{
    public $func = null;
    public $inp = null;

    public static function _($type, $args = null)
    {
        return new static($type, $args);
    }

    public function __construct($type, $args = null)
    {
        if ($type instanceof FormalType)
        {
            $this->func = $type->func;
            $this->inp = $type->inp;
        }
        else
        {
            $method = is_string($type) ? 't_' . strtolower(trim((string)$type)) : null;
            $this->func = $method && method_exists($this, $method) ? $method : (is_callable($type) ? $type : null);
            $this->inp = $args;
        }
    }

    public function __destruct()
    {
        $this->func = null;
        $this->inp = null;
    }

    public function exec($v, $k = null, $m = null)
    {
        if (is_string($this->func))
        {
            $v = call_user_func(array($this, $this->func), $v, $k, $m);
        }
        elseif (is_callable($this->func))
        {
            $v = call_user_func($this->func, $v, $this->inp, $k, $m);
        }
        return $v;
    }

    public function t_composite($v, $k, $m)
    {
        $types = (array)$this->inp;
        for ($i=0,$l=count($types); $i<$l; ++$i)
        {
            $v = $types[$i]->exec($v, $k, $m);
        }
        return $v;
    }

    /*public function t_fields($v, $k, $m)
    {
        if (!is_array($v)) return $v;
        $SEPARATOR = $m->option('SEPARATOR');
        foreach ($this->inp as $field => $type)
        {
            $v[$field] = $type->exec(isset($v[$field]) ? $v[$field] : null, empty($k) ? $field : "{$k}{$SEPARATOR}{$field}", $m);
        }
        return $v;
    }

    public function t_default($v, $k, $m)
    {
        $defaultValue = $this->inp;
        if (is_null($v) || (is_string($v) && !strlen(trim($v))))
        {
            $v = $defaultValue;
        }
        return $v;
    }*/

    public function t_bool($v, $k, $m)
    {
        // handle string representation of booleans as well
        if (is_string($v) && strlen($v))
        {
            $vs = strtolower($v);
            return 'true' === $vs || 'on' === $vs || '1' === $vs;
        }
        return (bool)$v;
    }

    public function t_int($v, $k, $m)
    {
        return intval($v);
    }

    public function t_float($v, $k, $m)
    {
        return floatval($v);
    }

    public function t_str($v, $k, $m)
    {
        return (string)$v;
    }

    public function t_min($v, $k, $m)
    {
        $min = $this->inp;
        return $v < $min ? $min : $v;
    }

    public function t_max($v, $k, $m)
    {
        $max = $this->inp;
        return $v > $max ? $max : $v;
    }

    public function t_clamp($v, $k, $m)
    {
        $min = $this->inp[0]; $max = $this->inp[1];
        return $v < $min ? $min : ($v > $max ? $max : $v);
    }

    public function t_trim($v, $k, $m)
    {
        return trim((string)$v);
    }

    public function t_lower($v, $k, $m)
    {
        return strtolower((string)$v);
    }

    public function t_upper($v, $k, $m)
    {
        return strtoupper((string)$v);
    }
}

class FormalValidator
{
    public $func = null;
    public $inp = null;
    public $msg = null;

    public static function strlen($s)
    {
        return function_exists('mb_strlen') ? mb_strlen((string)$s, 'UTF-8') : strlen((string)$s);
    }

    public static function _($validator, $args = null, $msg = null)
    {
        return new static($validator, $args, $msg);
    }

    public function __construct($validator, $args = null, $msg = null)
    {
        if ($validator instanceof FormalValidator)
        {
            $this->func = $validator->func;
            $this->inp = $validator->inp;
            $this->msg = empty($msg) ? $validator->msg : $msg;
        }
        else
        {
            $method = is_string($validator) ? 'v_' . strtolower(trim((string)$validator)) : null;
            $this->func = $method && method_exists($this, $method) ? $method : (is_callable($validator) ? $validator : null);
            $this->inp = $args;
            $this->msg = $msg;
        }
    }

    public function __destruct()
    {
        $this->func = null;
        $this->inp = null;
        $this->msg = null;
    }

    public function _and_($validator)
    {
        return new static('and', array($this, $validator));
    }

    public function _or_($validator)
    {
        return new static('or', array($this, $validator));
    }

    public function _not_($msg = null)
    {
        return new static('not', $this, $msg);
    }

    public function exec($v, $k = null, $m = null, $missingValue = false)
    {
        $valid = true;
        if (is_string($this->func))
        {
            $valid = (bool)call_user_func(array($this, $this->func), $v, $k, $m, $missingValue);
        }
        elseif (is_callable($this->func))
        {
            $valid = (bool)call_user_func($this->func, $v, $this->inp, $k, $m, $missingValue, $this->msg);
        }
        return $valid;
    }

    public function v_and($v, $k, $m, $missingValue)
    {
        $valid = $this->inp[0]->exec($v, $k, $m, $missingValue) && $this->inp[1]->exec($v, $k, $m, $missingValue);
        return $valid;
    }

    public function v_or($v, $k, $m, $missingValue)
    {
        $msg1 = null;
        $msg2 = null;
        $valid2 = false;
        try {
            $valid1 = $this->inp[0]->exec($v, $k, $m, $missingValue);
        } catch (FormalException $e) {
            $valid1 = false;
            $msg1 = $e->getMessage();
        }
        if (!$valid1)
        {
            try {
                $valid2 = $this->inp[1]->exec($v, $k, $m, $missingValue);
            } catch (FormalException $e) {
                $valid2 = false;
                $msg2 = $e->getMessage();
            }
        }
        $valid = $valid1 || $valid2;
        if (!$valid && (!empty($msg1) || !empty($msg2))) throw new FormalException(empty($msg1) ? $msg2 : $msg1);
        return $valid;
    }

    public function v_not($v, $k, $m, $missingValue)
    {
        try {
            $valid = !$this->inp->exec($v, $k, $m, $missingValue);
        } catch (FormalException $e) {
            $valid = true;
        }
        if (!$valid && !empty($this->msg)) throw new FormalException(str_replace(array('{key}', '{args}'), array($k, ''), $this->msg));
        return $valid;
    }

    public function v_optional($v, $k, $m, $missingValue)
    {
        $valid = true;
        if (!$missingValue)
        {
            $valid = $this->inp->exec($v, $k, $m, false);
        }
        return $valid;
    }

    public function v_required($v, $k, $m, $missingValue)
    {
        $valid = !$missingValue && !is_null($v);
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, ''), $this->msg) : "\"$k\" is required!");
        return $valid;
    }

    /*public function v_fields($v, $k, $m, $missingValue)
    {
        if (!is_array($v)) return false;
        $SEPARATOR = $m->option('SEPARATOR');
        foreach ($this->inp as $field => $validator)
        {
            if (!array_key_exists($field, $v))
            {
                if (!$validator->exec(null, empty($k) ? $field : "{$k}{$SEPARATOR}{$field}", $m, true))
                    return false;
            }
            else
            {
                if (!$validator->exec($v[$field], empty($k) ? $field : "{$k}{$SEPARATOR}{$field}", $m, $missingValue))
                    return false;
            }
        }
        return true;
    }*/

    public function v_numeric($v, $k, $m, $missingValue)
    {
        $valid = is_numeric($v);
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, ''), $this->msg) : "\"$k\" must be numeric value!");
        return $valid;
    }

    public function v_object($v, $k, $m, $missingValue)
    {
        $valid = is_object($v);
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, ''), $this->msg) : "\"$k\" must be an object!");
        return $valid;
    }

    public function v_array($v, $k, $m, $missingValue)
    {
        $valid = is_array($v);
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, ''), $this->msg) : "\"$k\" must be an array!");
        return $valid;
    }

    public function v_file($v, $k, $m, $missingValue)
    {
        $valid = is_file((string)$v);
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, ''), $this->msg) : "\"$k\" must be a file!");
        return $valid;
    }

    public function v_empty($v, $k, $m, $missingValue)
    {
        $valid = $missingValue || is_null($v) || (is_array($v) ? !count($v) : !strlen(trim((string)$v)));
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, ''), $this->msg) : "\"$k\" must be empty!");
        return $valid;
    }

    public function v_maxitems($v, $k, $m, $missingValue)
    {
        $valid = count($v) <= $this->inp;
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, $this->inp), $this->msg) : "\"$k\" must have at most {$this->inp} items!");
        return $valid;
    }

    public function v_minitems($v, $k, $m, $missingValue)
    {
        $valid = count($v) >= $this->inp;
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, $this->inp), $this->msg) : "\"$k\" must have at least {$this->inp} items!");
        return $valid;
    }

    public function v_maxchars($v, $k, $m, $missingValue)
    {
        $valid = static::strlen($v) <= $this->inp;
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, $this->inp), $this->msg) : "\"$k\" must have at most {$this->inp} characters!");
        return $valid;
    }

    public function v_minchars($v, $k, $m, $missingValue)
    {
        $valid = static::strlen($v) >= $this->inp;
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, $this->inp), $this->msg) : "\"$k\" must have at least {$this->inp} characters!");
        return $valid;
    }

    public function v_maxsize($v, $k, $m, $missingValue)
    {
        $fs = false;
        try {
            $fs = @filesize((string)$v);
        } catch (Exception $e) {
            $fs = false;
        }
        $valid = false === $fs ? false : $fs <= $this->inp;
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, $this->inp), $this->msg) : "\"$k\" must have at most {$this->inp} bytes!");
        return $valid;
    }

    public function v_minsize($v, $k, $m, $missingValue)
    {
        $fs = false;
        try {
            $fs = @filesize((string)$v);
        } catch (Exception $e) {
            $fs = false;
        }
        $valid = false === $fs ? false : $fs >= $this->inp;
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, $this->inp), $this->msg) : "\"$k\" must have at least {$this->inp} bytes!");
        return $valid;
    }

    public function v_eq($v, $k, $m, $missingValue)
    {
        $val = $this->inp; $valm = $val;
        if ($val instanceof FormalField)
        {
            $valm = !empty($this->msg) ? $val->field : '"' . $val->field . '"';
            $val = $m->get($val->field);
        }
        $valid = $val === $v;
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, $valm), $this->msg) : "\"$k\" must be equal to {$valm}!");
        return $valid;
    }

    public function v_neq($v, $k, $m, $missingValue)
    {
        $val = $this->inp; $valm = $val;
        if ($val instanceof FormalField)
        {
            $valm = !empty($this->msg) ? $val->field : '"' . $val->field . '"';
            $val = $m->get($val->field);
        }
        $valid = $val != $v;
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, $valm), $this->msg) : "\"$k\" must not be equal to {$valm}!");
        return $valid;
    }

    public function v_gt($v, $k, $m, $missingValue)
    {
        $val = $this->inp; $valm = $val;
        if ($val instanceof FormalField)
        {
            $valm = !empty($this->msg) ? $val->field : '"' . $val->field . '"';
            $val = $m->get($val->field);
        }
        $valid = $v > $val;
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, $valm), $this->msg) : "\"$k\" must be greater than {$valm}!");
        return $valid;
    }

    public function v_gte($v, $k, $m, $missingValue)
    {
        $val = $this->inp; $valm = $val;
        if ($val instanceof FormalField)
        {
            $valm = !empty($this->msg) ? $val->field : '"' . $val->field . '"';
            $val = $m->get($val->field);
        }
        $valid = $v >= $val;
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, $valm), $this->msg) : "\"$k\" must be greater than or equal to {$valm}!");
        return $valid;
    }

    public function v_lt($v, $k, $m, $missingValue)
    {
        $val = $this->inp; $valm = $val;
        if ($val instanceof FormalField)
        {
            $valm = !empty($this->msg) ? $val->field : '"' . $val->field . '"';
            $val = $m->get($val->field);
        }
        $valid = $v < $val;
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, $valm), $this->msg) : "\"$k\" must be less than {$valm}!");
        return $valid;
    }

    public function v_lte($v, $k, $m, $missingValue)
    {
        $val = $this->inp; $valm = $val;
        if ($val instanceof FormalField)
        {
            $valm = !empty($this->msg) ? $val->field : '"' . $val->field . '"';
            $val = $m->get($val->field);
        }
        $valid = $v <= $val;
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, $valm), $this->msg) : "\"$k\" must be less than or equal to {$valm}!");
        return $valid;
    }

    public function v_between($v, $k, $m, $missingValue)
    {
        $min = $this->inp[0];
        $max = $this->inp[1];
        $minm = $min; $maxm = $max;
        if ($min instanceof FormalField)
        {
            $minm = !empty($this->msg) ? $min->field : '"' . $min->field . '"';
            $min = $m->get($min->field);
        }
        if ($max instanceof FormalField)
        {
            $maxm = !empty($this->msg) ? $max->field : '"' . $max->field . '"';
            $max = $m->get($max->field);
        }
        $valid = ($min <= $v) && ($v <= $max);
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, implode(',', array($minm, $maxm))), $this->msg) : "\"$k\" must be between {$minm} and {$maxm}!");
        return $valid;
    }

    public function v_in($v, $k, $m, $missingValue)
    {
        $val = $this->inp;
        if ($val instanceof FormalField)
        {
            $valm = !empty($this->msg) ? $val->field : '"' . $val->field . '"';
            $val = $m->get($val->field);
        }
        else
        {
            $valm = !empty($this->msg) ? implode(',', (array)$val) : '[' . implode(',', (array)$val) . ']';
        }
        $valid = in_array($v, (array)$val);
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, $valm), $this->msg) : "\"$k\" must be one of {$valm}!");
        return $valid;
    }

    public function v_not_in($v, $k, $m, $missingValue)
    {
        $val = $this->inp;
        if ($val instanceof FormalField)
        {
            $valm = !empty($this->msg) ? $val->field : '"' . $val->field . '"';
            $val = $m->get($val->field);
        }
        else
        {
            $valm = !empty($this->msg) ? implode(',', (array)$val) : '[' . implode(',', (array)$val) . ']';
        }
        $valid = !in_array($v, (array)$val);
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, $valm), $this->msg) : "\"$k\" must not be one of {$valm}!");
        return $valid;
    }

    public function v_match($v, $k, $m, $missingValue)
    {
        $valid = (bool)preg_match((string)$this->inp, (string)$v);
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, $this->inp instanceof FormalDateTime ? $this->inp->getFormat() : $this->inp), $this->msg) : "\"$k\" must match " . ($this->inp instanceof FormalDateTime ? '"' . $this->inp->getFormat() . '"' : 'the') . " pattern!");
        return $valid;
    }

    public function v_email($v, $k, $m, $missingValue)
    {
        $valid = (bool)preg_match('/^(([^<>()[\\]\\\\.,;:\\s@\\"]+(\\.[^<>()[\\]\\\\.,;:\\s@\\"]+)*)|(\\".+\\"))@((\\[[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\])|(([a-zA-Z\\-0-9]+\\.)+[a-zA-Z]{2,}))$/', (string)$v);
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, ''), $this->msg) : "\"$k\" must be valid email pattern!");
        return $valid;
    }

    public function v_url($v, $k, $m, $missingValue)
    {
        $valid = (bool)preg_match('/^(?!mailto:)(?:(?:http|https|ftp)://)(?:\\S+(?::\\S*)?@)?(?:(?:(?:[1-9]\\d?|1\\d\\d|2[01]\\d|22[0-3])(?:\\.(?:1?\\d{1,2}|2[0-4]\\d|25[0-5])){2}(?:\\.(?:[0-9]\\d?|1\\d\\d|2[0-4]\\d|25[0-4]))|(?:(?:[a-z\\u00a1-\\uffff0-9]+-?)*[a-z\\u00a1-\\uffff0-9]+)(?:\\.(?:[a-z\\u00a1-\\uffff0-9]+-?)*[a-z\\u00a1-\\uffff0-9]+)*(?:\\.(?:[a-z\\u00a1-\\uffff]{2,})))|localhost)(?::\\d{2,5})?(?:(/|\\?|#)[^\\s]*)?$/i', (string)$v);
        if (!$valid) throw new FormalException(!empty($this->msg) ? str_replace(array('{key}', '{args}'), array($k, ''), $this->msg) : "\"$k\" must be valid url pattern!");
        return $valid;
    }
}

class FormalError
{
    private $key = array();
    private $msg = '';

    public function __construct($msg = '', $key = array())
    {
        $this->msg = (string)$msg;
        $this->key = $key;
    }

    public function getMsg()
    {
        return $this->msg;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function __toString()
    {
        return $this->msg;
    }
}

class Formal
{
    const VERSION = "1.1.1";

    public static function field($field)
    {
        return new FormalField($field);
    }

    public static function datetime($format, $locale = null)
    {
        return new FormalDateTime($format, $locale);
    }

    public static function typecast($type, $args = null)
    {
        return new FormalType($type, $args);
    }

    public static function validate($validator, $args = null, $msg = null)
    {
        return new FormalValidator($validator, $args, $msg);
    }

    private $opts = array();
    private $err = array();
    private $data = null;

    public function __construct()
    {
        // some defaults
        $this->option('WILDCARD', '*');
        $this->option('SEPARATOR', '.');
        $this->option('break_on_first_error', false);
        $this->option('invalid_value_msg', 'Invalid Value in "{key}"!');
        $this->option('missing_value_msg', 'Missing Value in "{key}"!');
        $this->option('defaults', array());
        $this->option('typecasters', array());
        $this->option('validators', array());
    }

    public function option($key, $val = null)
    {
        $nargs = func_num_args();
        if (1 == $nargs)
        {
            return isset($this->opts[$key]) ? $this->opts[$key] : null;
        }
        elseif (1 < $nargs)
        {
            $this->opts[$key] = $val;
        }
        return $this;
    }

    public function process($data)
    {
        $WILDCARD = $this->option('WILDCARD');
        $SEPARATOR = $this->option('SEPARATOR');
        $this->data = null;
        $this->err = array();
        //$data = $this->clone($data);
        $data = $this->doMergeDefaults($data, $this->option('defaults'), $WILDCARD, $SEPARATOR);
        $data = $this->doTypecast($data, $this->option('typecasters'), array(), array(), $WILDCARD, $SEPARATOR);
        $this->data = $data;
        $this->doValidate($data, $this->option('validators'), array(), array(), $WILDCARD, $SEPARATOR);
        $this->data = null;
        return $data;
    }

    public function getErrors()
    {
        return $this->err;
    }

    public function get($field, $default = null, $data = null)
    {
        if (null === $data) $data = $this->data;
        $WILDCARD = $this->option('WILDCARD');
        $SEPARATOR = $this->option('SEPARATOR');
        $is_array_result = false;
        $is_result_set = false;
        $result = null;
        if ((is_string($field) || is_numeric($field)) && (is_object($data) || is_array($data)))
        {
            $stack = array(array(&$data, (string)$field));
            while (!empty($stack))
            {
                $to_get = array_shift($stack);
                $o =& $to_get[0];
                $key = $to_get[1];
                $p = explode($SEPARATOR, $key);
                $i = 0;
                $l = count($p);
                while ($i < $l)
                {
                    $k = $p[$i++];
                    if ($i < $l)
                    {
                        if (is_object($o))
                        {
                            if ($WILDCARD === $k)
                            {
                                $is_array_result = true;
                                $k = implode($SEPARATOR, array_slice($p, $i));
                                foreach (array_keys((array)$o) as $key)
                                {
                                    $stack[] = array(&$o, "{$key}{$SEPARATOR}{$k}");
                                }
                                break;
                            }
                            elseif (property_exists($o, $k))
                            {
                                $o =& $o->{$k};
                            }
                            else
                            {
                                break;
                            }
                        }
                        elseif (is_array($o))
                        {
                            if ($WILDCARD === $k)
                            {
                                $is_array_result = true;
                                $k = implode($SEPARATOR, array_slice($p, $i));
                                foreach (array_keys($o) as $key)
                                {
                                    $stack[] = array(&$o, "{$key}{$SEPARATOR}{$k}");
                                }
                                break;
                            }
                            elseif (array_key_exists($k, $o))
                            {
                                $o =& $o[$k];
                            }
                            else
                            {
                                break;
                            }
                        }
                        else
                        {
                            break;
                        }
                    }
                    else
                    {
                        if (is_object($o))
                        {
                            if ($WILDCARD === $k)
                            {
                                $is_array_result = true;
                                if (!$is_result_set) $result = array();
                                foreach (array_keys((array)$o) as $k)
                                {
                                    $result[] = $o->{$k};
                                }
                                $is_result_set = true;
                            }
                            elseif (property_exists($o, $k))
                            {
                                if ($is_array_result)
                                {
                                    if (!$is_result_set) $result = array();
                                    $result[] = $o->{$k};
                                }
                                else
                                {
                                    $result = $o->{$k};
                                }
                                $is_result_set = true;
                            }
                            else
                            {
                                if ($is_array_result)
                                {
                                    if (!$is_result_set) $result = array();
                                    $result[] = $default;
                                }
                                else
                                {
                                    $result = $default;
                                }
                                $is_result_set = true;
                            }
                        }
                        elseif (is_array($o))
                        {
                            if ($WILDCARD === $k)
                            {
                                $is_array_result = true;
                                if (!$is_result_set) $result = array();
                                foreach (array_keys($o) as $k)
                                {
                                    $result[] = $o[$k];
                                }
                                $is_result_set = true;
                            }
                            elseif (array_key_exists($k, $o))
                            {
                                if ($is_array_result)
                                {
                                    if (!$is_result_set) $result = array();
                                    $result[] = $o[$k];
                                }
                                else
                                    $result = $o[$k];
                                $is_result_set = true;
                            }
                            else
                            {
                                if ($is_array_result)
                                {
                                    if (!$is_result_set) $result = array();
                                    $result[] = $default;
                                }
                                else
                                {
                                    $result = $default;
                                }
                                $is_result_set = true;
                            }
                        }
                    }
                }
            }
            return $is_result_set ? $result : $default;
        }
        return $default;
    }

    private function clone($o)
    {
        if (is_array($o))
        {
            $oo = array();
            foreach ($o as $k => $v) $oo[$k] = $this->clone($v);
            return $oo;
        }
        else
        {
            return $o;
        }
    }

    private function doMergeKeys($keys, $def)
    {
        $n = count($keys);
        $defaults = $def;
        for ($i=$n-1; $i>=0; --$i)
        {
            $o = array();
            $k = $keys[$i];
            if (is_array($k))
            {
                foreach ($k as $kk)
                {
                    $o[$kk] = $this->clone($defaults);
                }
            }
            else
            {
                $o[$k] = $defaults;
            }
            $defaults = $o;
        }
        return $defaults;
    }

    private function doMergeDefaults($data, $defaults, $WILDCARD = '*', $SEPARATOR = '.')
    {
        if (is_array($data) && is_array($defaults))
        {
            foreach ($defaults as $key => $def)
            {
                $kk = explode($SEPARATOR, $key);
                $n = count($kk);
                if (1 < $n)
                {
                    $o = $data;
                    $keys = array();
                    $doMerge = true;
                    for ($i=0; $i<$n; ++$i)
                    {
                        $k = $kk[$i];
                        if ($WILDCARD === $k)
                        {
                            $ok = array_keys($o);
                            if (empty($ok))
                            {
                                $doMerge = false;
                                break;
                            }
                            $keys[] = $ok;
                            $o = $o[$ok[0]];
                        }
                        elseif (array_key_exists($k, $o))
                        {
                            $keys[] = $k;
                            $o = $o[$k];
                        }
                        elseif ($i === $n-1)
                        {
                            $keys[] = $k;
                        }
                        else
                        {
                            $doMerge = false;
                            break;
                        }
                    }
                    if ($doMerge)
                    {
                        $data = $this->doMergeDefaults($data, $this->doMergeKeys($keys, $def), $WILDCARD, $SEPARATOR);
                    }
                }
                else
                {
                    if (array_key_exists($key, $data))
                    {
                        if (is_array($data[$key]) && is_array($def))
                        {
                            $data[$key] = $this->doMergeDefaults($data[$key], $def, $WILDCARD, $SEPARATOR);
                        }
                        elseif (is_null($data[$key]) || (is_string($data[$key]) && !strlen(trim($data[$key]))))
                        {
                            $data[$key] = $this->clone($def);
                        }
                    }
                    else
                    {
                        $data[$key] = $this->clone($def);
                    }
                }
            }
        }
        elseif (is_null($data) || (is_string($data) && !strlen(trim($data))))
        {
            $data = $this->clone($defaults);
        }
        return $data;
    }

    private function doTypecast($data, $typecaster, $key = array(), $root = array(), $WILDCARD = '*', $SEPARATOR = '.')
    {
        if ($typecaster instanceof FormalType)
        {
            $n = count($key); $i = 0;
            if ($i < $n)
            {
                $k = $key[$i++];
                if ('' === $k)
                {
                    return $data;
                }
                elseif ($WILDCARD === $k)
                {
                    if ($i < $n)
                    {
                        $rk = array_slice($key, $i);
                        $root = array_merge($root, array_slice($key, 0, $i-1));
                        foreach (array_keys($data) as $ok)
                        {
                            $data[$ok] = $this->doTypecast($data[$ok], $typecaster, $rk, array_merge($root, array($ok)), $WILDCARD, $SEPARATOR);
                        }
                    }
                    else
                    {
                        $root = array_merge($root, array_slice($key, 0, $i-1));
                        foreach (array_keys($data) as $ok)
                        {
                            $data = $this->doTypecast($data, $typecaster, array($ok), $root, $WILDCARD, $SEPARATOR);
                        }
                    }
                    return $data;
                }
                elseif (array_key_exists($k, $data))
                {
                    $rk = array_slice($key, $i);
                    $root = array_merge($root, array_slice($key, 0, $i));
                    $data[$k] = $this->doTypecast($data[$k], $typecaster, $rk, $root, $WILDCARD, $SEPARATOR);
                }
                else
                {
                    return $data;
                }
            }
            else
            {
                $KEY = implode($SEPARATOR, array_merge($root, $key));
                $data = $typecaster->exec($data, $KEY, $this);
            }
        }
        elseif (is_array($typecaster))
        {
            foreach ($typecaster as $k => $t)
            {
                $data = $this->doTypecast($data, $t, empty($key) ? explode($SEPARATOR, $k) : array_merge($key, explode($SEPARATOR, $k)), $root, $WILDCARD, $SEPARATOR);
            }
        }
        return $data;
    }

    private function doValidate($data, $validator, $key = array(), $root = array(), $WILDCARD = '*', $SEPARATOR = '.')
    {
        if ($this->option('break_on_first_error') && !empty($this->err)) return;
        if ($validator instanceof FormalValidator)
        {
            $n = count($key); $i = 0;
            while ($i < $n)
            {
                $k = $key[$i++];
                if ('' === $k)
                {
                    continue;
                }
                elseif ($WILDCARD === $k)
                {
                    if ($i < $n)
                    {
                        $rk = array_slice($key, $i);
                        $root = array_merge($root, array_slice($key, 0, $i-1));
                        foreach (array_keys($data) as $ok)
                        {
                            $this->doValidate($data[$ok], $validator, $rk, array_merge($root, array($ok)), $WILDCARD, $SEPARATOR);
                        }
                    }
                    else
                    {
                        $root = array_merge($root, array_slice($key, 0, $i-1));
                        foreach (array_keys($data) as $ok)
                        {
                            $this->doValidate($data, $validator, array($ok), $root, $WILDCARD, $SEPARATOR);
                        }
                    }
                    return;
                }
                elseif (array_key_exists($k, $data))
                {
                    $data = $data[$k];
                }
                else
                {
                    $KEY_ = array_merge($root, $key);
                    $KEY = implode($SEPARATOR, $KEY_);
                    $err = null;
                    try {
                        $valid = $validator->exec(null, $KEY, $this, true);
                    } catch (FormalException $e) {
                        $valid = false;
                        $err = $e->getMessage();
                    }
                    if (!$valid)
                    {
                        $this->err[] = new FormalError(empty($err) ? str_replace(array('{key}', '{args}'), array($KEY, ''), $this->option('missing_value_msg')) : $err, $KEY_);
                    }
                    return;
                }
            }

            $KEY_ = array_merge($root, $key);
            $KEY = implode($SEPARATOR, $KEY_);
            $err = null;
            try {
                $valid = $validator->exec($data, $KEY, $this, false);
            } catch (FormalException $e) {
                $valid = false;
                $err = $e->getMessage();
            }
            if (!$valid)
            {
                $this->err[] = new FormalError(empty($err) ? str_replace(array('{key}', '{args}'), array($KEY, ''), $this->option('invalid_value_msg')) : $err, $KEY_);
            }
        }
        elseif (is_array($validator))
        {
            foreach ($validator as $k => $v)
            {
                $this->doValidate($data, $v, empty($key) ? explode($SEPARATOR, $k) : array_merge($key, explode($SEPARATOR, $k)), $root, $WILDCARD, $SEPARATOR);
            }
        }
    }
}
}