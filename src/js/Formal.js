/**
*   Formal
*   validate nested (form) data with built-in and custom rules for PHP, JavaScript, Python
*
*   @version 1.0.0
*   https://github.com/foo123/Formal
*
**/
!function(root, name, factory) {
"use strict";
if (('object' === typeof module) && module.exports) /* CommonJS */
    (module.$deps = module.$deps||{}) && (module.exports = module.$deps[name] = factory.call(root));
else if (('function' === typeof define) && define.amd && ('function' === typeof require) && ('function' === typeof require.specified) && require.specified(name) /*&& !require.defined(name)*/) /* AMD */
    define(name, ['module'], function(module) {factory.moduleUri = module.uri; return factory.call(root);});
else if (!(name in root)) /* Browser/WebWorker/.. */
    (root[name] = factory.call(root)||1) && ('function' === typeof(define)) && define.amd && define(function() {return root[name];});
}(  /* current root */          'undefined' !== typeof self ? self : this,
    /* module name */           "Formal",
    /* module factory */        function ModuleFactory__Formal(undef) {
"use strict";

var HAS = Object.prototype.hasOwnProperty,
    toString = Object.prototype.toString,
    ESC_RE = /[.*+?^${}()|[\]\\]/g,
    EMAIL_RE = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/,
    URL_RE = new RegExp('^(?!mailto:)(?:(?:http|https|ftp)://)(?:\\S+(?::\\S*)?@)?(?:(?:(?:[1-9]\\d?|1\\d\\d|2[01]\\d|22[0-3])(?:\\.(?:1?\\d{1,2}|2[0-4]\\d|25[0-5])){2}(?:\\.(?:[0-9]\\d?|1\\d\\d|2[0-4]\\d|25[0-4]))|(?:(?:[a-z\\u00a1-\\uffff0-9]+-?)*[a-z\\u00a1-\\uffff0-9]+)(?:\\.(?:[a-z\\u00a1-\\uffff0-9]+-?)*[a-z\\u00a1-\\uffff0-9]+)*(?:\\.(?:[a-z\\u00a1-\\uffff]{2,})))|localhost)(?::\\d{2,5})?(?:(/|\\?|#)[^\\s]*)?$','i'),
    isNode = ("undefined" !== typeof global) && ("[object global]" === toString.call(global)),
    isBrowser = ("undefined" !== typeof window) && ("[object Window]" === toString.call(window))
;

function is_numeric(x)
{
    return !isNaN(+x);
}
function is_string(x)
{
    return ('string' === typeof(x)) || ('[object String]' === toString.call(x));
}
function is_array(x)
{
    return ('[object Array]' === toString.call(x));
}
function is_object(x)
{
    return ('[object Object]' === toString.call(x)) && ('function' === typeof x.constructor) && ('Object' === x.constructor.name);
}
async function is_file(x)
{
    if (isNode)
    {
        return await new Promise(function(resolve) {
            require('fs').lstat(String(x), function(err, stats) {
                resolve(err || !stats ? false : stats.isFile());
            });
        });
    }
    else if (isBrowser)
    {
        return ('File' in window) && (x instanceof File);
    }
    return false;
}
async function filesize(x)
{
    if (isNode)
    {
        return await new Promise(function(resolve) {
            require('fs').lstat(String(x), function(err, stats) {
                resolve(err || !stats ? false : stats.size);
            });
        });
    }
    else if (isBrowser)
    {
        return ('File' in window) && (x instanceof File) ? x.size : false;
    }
    return false;
}
function is_callable(x)
{
    return 'function' === typeof(x);
}
function method_exists(o, m)
{
    return is_callable(o[m]);
}
function array(a)
{
    return is_array(a) ? a : [a];
}
function empty(x)
{
    return (null == x) || (0 === x) || (false === x) || ('' === x) || (is_array(x) && !x.length) || (is_object(x) && !Object.keys(x).length);
}
function is_null(x)
{
    return null == x;
}
function esc_re(s)
{
    return s.replace(ESC_RE, '\\$&');
}
function by_length_desc(a, b)
{
    return b.length-a.length;
}
function get_alternate_pattern(alts)
{
    alts.sort(by_length_desc);
    return alts.map(esc_re).join('|');
}
function clone(o)
{
    if (is_array(o))
    {
        return o.map(clone);
    }
    else if (is_object(o))
    {
        return Object.keys(o).reduce(function(oo, k) {
            oo[k] = clone(o[k]);
            return oo;
        }, {});
    }
    else
    {
        return o;
    }
}

class FormalException extends Error
{
    constructor(message) {
        super(message);
        this.name = "FormalException";
    }
}

class FormalField
{
    field = null;

    constructor(field) {
        this.field = field;
    }
}

class FormalDateTime
{
    format = '';
    pattern = null;

    constructor(format, locale = null) {
        if (!locale) locale = {
            'day_short' : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'day' : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'month_short' : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'month' : ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            'meridian' : {'am' : 'am', 'pm' : 'pm', 'AM' : 'AM', 'PM' : 'PM'},
            'timezone_short' : ['UTC'],
            'timezone' : ['UTC'],
            'ordinal' : {'ord' : {'1' : 'st', '2' : 'nd', '3' : 'rd'}, 'nth' : 'th'},
        };

        // (php) date formats
        // http://php.net/manual/en/function.date.php
        var D = {
            // Day --
            // Day of month w/leading 0; 01..31
             'd': '(31|30|29|28|27|26|25|24|23|22|21|20|19|18|17|16|15|14|13|12|11|10|09|08|07|06|05|04|03|02|01)'
            // Shorthand day name; Mon...Sun
            ,'D': '(' + get_alternate_pattern(locale['day_short']) + ')'
            // Day of month; 1..31
            ,'j': '(31|30|29|28|27|26|25|24|23|22|21|20|19|18|17|16|15|14|13|12|11|10|9|8|7|6|5|4|3|2|1)'
            // Full day name; Monday...Sunday
            ,'l': '(' + get_alternate_pattern(locale['day']) + ')'
            // ISO-8601 day of week; 1[Mon]..7[Sun]
            ,'N': '([1-7])'
            // Ordinal suffix for day of month; st, nd, rd, th
            ,'S': '' // added below
            // Day of week; 0[Sun]..6[Sat]
            ,'w': '([0-6])'
            // Day of year; 0..365
            ,'z': '([1-3]?[0-9]{1,2})'

            // Week --
            // ISO-8601 week number
            ,'W': '([0-5]?[0-9])'

            // Month --
            // Full month name; January...December
            ,'F': '(' + get_alternate_pattern(locale['month']) + ')'
            // Month w/leading 0; 01...12
            ,'m': '(12|11|10|09|08|07|06|05|04|03|02|01)'
            // Shorthand month name; Jan...Dec
            ,'M': '(' + get_alternate_pattern(locale['month_short']) + ')'
            // Month; 1...12
            ,'n': '(12|11|10|9|8|7|6|5|4|3|2|1)'
            // Days in month; 28...31
            ,'t': '(31|30|29|28)'

            // Year --
            // Is leap year?; 0 or 1
            ,'L': '([01])'
            // ISO-8601 year
            ,'o': '(\\d{2,4})'
            // Full year; e.g. 1980...2010
            ,'Y': '([12][0-9]{3})'
            // Last two digits of year; 00...99
            ,'y': '([0-9]{2})'

            // Time --
            // am or pm
            ,'a': '(' + get_alternate_pattern([
                locale['meridian']['am'],
                locale['meridian']['pm']
            ]) + ')'
            // AM or PM
            ,'A': '(' + get_alternate_pattern([
                locale['meridian']['AM'],
                locale['meridian']['PM']
            ]) + ')'
            // Swatch Internet time; 000..999
            ,'B': '([0-9]{3})'
            // 12-Hours; 1..12
            ,'g': '(12|11|10|9|8|7|6|5|4|3|2|1)'
            // 24-Hours; 0..23
            ,'G': '(23|22|21|20|19|18|17|16|15|14|13|12|11|10|9|8|7|6|5|4|3|2|1|0)'
            // 12-Hours w/leading 0; 01..12
            ,'h': '(12|11|10|09|08|07|06|05|04|03|02|01)'
            // 24-Hours w/leading 0; 00..23
            ,'H': '(23|22|21|20|19|18|17|16|15|14|13|12|11|10|09|08|07|06|05|04|03|02|01|00)'
            // Minutes w/leading 0; 00..59
            ,'i': '([0-5][0-9])'
            // Seconds w/leading 0; 00..59
            ,'s': '([0-5][0-9])'
            // Microseconds; 000000-999000
            ,'u': '([0-9]{6})'

            // Timezone --
            // Timezone identifier; e.g. Atlantic/Azores, ...
            ,'e': '(' + get_alternate_pattern(locale['timezone']) + ')'
            // DST observed?; 0 or 1
            ,'I': '([01])'
            // Difference to GMT in hour format; e.g. +0200
            ,'O': '([+-][0-9]{4})'
            // Difference to GMT w/colon; e.g. +02:00
            ,'P': '([+-][0-9]{2}:[0-9]{2})'
            // Timezone abbreviation; e.g. EST, MDT, ...
            ,'T': '(' + get_alternate_pattern(locale['timezone_short']) + ')'
            // Timezone offset in seconds (-43200...50400)
            ,'Z': '(-?[0-9]{5})'

            // Full Date/Time --
            // Seconds since UNIX epoch
            ,'U': '([0-9]{1,8})'
            // ISO-8601 date. Y-m-d\\TH:i:sP
            ,'c': '' // added below
            // RFC 2822 D, d M Y H:i:s O
            ,'r': '' // added below
        };
        // Ordinal suffix for day of month; st, nd, rd, th
        var lords = Object.values(locale['ordinal']['ord']);
        lords.push(locale['ordinal']['nth']);
        D['S'] = '(' + get_alternate_pattern(lords) + ')';
        // ISO-8601 date. Y-m-d\\TH:i:sP
        D['c'] = D['Y']+'-'+D['m']+'-'+D['d']+'\\\\'+D['T']+D['H']+':'+D['i']+':'+D['s']+D['P'];
        // RFC 2822 D, d M Y H:i:s O
        D['r'] = D['D']+',\\s'+D['d']+'\\s'+D['M']+'\\s'+D['Y']+'\\s'+D['H']+':'+D['i']+':'+D['s']+'\\s'+D['O'];

        var re = '', l = format.length, i, f;
        for (i=0; i<l; ++i)
        {
            f = format.charAt(i);
            re += HAS.call(D, f) ? D[ f ] : esc_re(f);
        }


        this.format = format;
        this.pattern = new RegExp('^' + re + '$', '');
    }

    getFormat() {
        return this.format;
    }

    getPattern() {
        return this.pattern;
    }

    toString() {
        return this.pattern.toString();
    }
}

class FormalType
{
    func = null;
    inp = null;

    constructor(type, args = null) {
        if (type instanceof FormalType)
        {
            this.func = type.func;
            this.inp = type.inp;
        }
        else
        {
            var method = is_string(type) ? 't_' + String(type).trim().toLowerCase() : null;
            this.func = method && method_exists(this, method) ? this[method].bind(this) : (is_callable(type) ? type : null);
            this.inp = args;
        }
    }

    async exec(v, k = null, m = null) {
        if (is_callable(this.func))
        {
            v = await this.func(v, k, m);
        }
        return v;
    }

    async t_composite(v, k, m) {
        var types = array(this.inp), l = types.length, i = 0;
        for (i=0; i<l; ++i)
        {
            v = await types[i].exec(v, k, m);
        }
        return v;
    }

    /*async t_fields(v, k, m) {
        if (!is_object(v) && !is_array(v)) return v;
        var SEPARATOR = m.option('SEPARATOR'), field, type;
        for (field in this.inp)
        {
            if (!HAS.call(this.inp, field)) continue;
            type = this.inp[field];
            v[field] = await type.exec(HAS.call(v, field) ? v[field] : undef, empty(k) ? field : k+SEPARATOR+field, m);
        }
        return v;
    }

    t_default(v, k, m) {
        var defaultValue = this.inp;
        if (is_null(v) || (is_string(v) && !v.trim().length))
        {
            v = defaultValue;
        }
        return v;
    }*/

    t_bool(v, k, m) {
        // handle string representation of booleans as well
        if (is_string(v) && v.length)
        {
            var vs = v.toLowerCase();
            return 'true' === vs || 'on' === vs || '1' === vs;
        }
        return !!v;
    }

    t_int(v, k, m) {
        return parseInt(v);
    }

    t_float(v, k, m) {
        return parseFloat(v);
    }

    t_str(v, k, m) {
        return String(v);
    }

    t_min(v, k, m) {
        var min = this.inp;
        return v < min ? min : v;
    }

    t_max(v, k, m) {
        var max = this.inp;
        return v > max ? max : v;
    }

    t_clamp(v, k, m) {
        var min = this.inp[0], max = this.inp[1];
        return v < min ? min : (v > max ? max : v);
    }

    t_trim(v, k, m) {
        return String(v).trim();
    }

    t_lower(v, k, m) {
        return String(v).toLowerCase();
    }

    t_upper(v, k, m) {
        return String(v).toUpperCase();
    }
}

class FormalValidator
{
    func = null;
    inp = null;
    msg = null;

    constructor(validator, args = null, msg = null) {
        if (validator instanceof FormalValidator)
        {
            this.func = validator.func;
            this.inp = validator.inp;
            this.msg = empty(msg) ? validator.msg : msg;
        }
        else
        {
            var method = is_string(validator) ? 'v_' + String(validator).trim().toLowerCase() : null;
            this.func = method && method_exists(this, method) ? this[method].bind(this) : (is_callable(validator) ? validator : null);
            this.inp = args;
            this.msg = msg;
        }
    }

    _and_(validator) {
        return new FormalValidator('and', [this, validator]);
    }

    _or_(validator) {
        return new FormalValidator('or', [this, validator]);
    }

    _not_() {
        return new FormalValidator('not', this);
    }

    async exec(v, k = null, m = null, missingValue = false) {
        var valid = true;
        if (is_callable(this.func))
        {
            valid = !!(await this.func(v, k, m, missingValue));
        }
        return valid;
    }

    async v_and(v, k, m, missingValue) {
        var valid = (await this.inp[0].exec(v, k, m, missingValue)) && (await this.inp[1].exec(v, k, m, missingValue));
        return valid;
    }

    async v_or(v, k, m, missingValue) {
        var msg1 = null, msg2 = null, valid1 = false, valid2 = false, valid;
        try {
            valid1 = await this.inp[0].exec(v, k, m, missingValue);
        } catch (e) {
            if (e instanceof FormalException)
            {
                valid1 = false;
                msg1 = e.message;
            }
            else
            {
                throw e;
            }
        }
        if (!valid1)
        {
            try {
                valid2 = await this.inp[1].exec(v, k, m, missingValue);
            } catch (e) {
                if (e instanceof FormalException)
                {
                    valid2 = false;
                    msg2 = e.message;
                }
                else
                {
                    throw e;
                }
            }
        }
        valid = valid1 || valid2;
        if (!valid && (!empty(msg1) || !empty(msg2))) throw new FormalException(empty(msg1) ? msg2 : msg1);
        return valid;
    }

    async v_not(v, k, m, missingValue) {
        var valid;
        try {
            valid = !(await this.inp.exec(v, k, m, missingValue));
        } catch (e) {
            if (e instanceof FormalException)
            {
                valid = true;
            }
            else
            {
                throw e;
            }
        }
        return valid;
    }

    async v_optional(v, k, m, missingValue) {
        var valid = true;
        if (!missingValue)
        {
            valid = await this.inp.exec(v, k, m, false);
        }
        return valid;
    }

    v_required(v, k, m, missingValue) {
        var valid = !missingValue && !is_null(v);
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', '') : "\""+k+"\" is required!");
        return valid;
    }

    /*async v_fields(v, k, m, missingValue) {
        if (!is_object(v) && !is_array(v)) return false;
        var SEPARATOR = m.option('SEPARATOR'), field, validator;
        for (field in this.inp)
        {
            if (!HAS.call(this.inp, field)) continue;
            validator = this.inp[field];
            if (!HAS.call(v, field))
            {
                if (!(await validator.exec(undef, empty(k) ? field : k+SEPARATOR+field, m, true)))
                    return false;
            }
            else
            {
                if (!(await validator.exec(v[field], empty(k) ? field : k+SEPARATOR+field, m, missingValue)))
                    return false;
            }
        }
        return true;
    }*/

    v_numeric(v, k, m, missingValue) {
        var valid = is_numeric(v);
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', '') : "\""+k+"\" must be numeric value!");
        return valid;
    }

    v_object(v, k, m, missingValue) {
        var valid = is_object(v);
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', '') : "\""+k+"\" must be an object!");
        return valid;
    }

    v_array(v, k, m, missingValue) {
        var valid = is_array(v);
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', '') : "\""+k+"\" must be an array!");
        return valid;
    }

    async v_file(v, k, m, missingValue) {
        var valid = await is_file(v);
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', '') : "\""+k+"\" must be a file!");
        return valid;
    }

    v_empty(v, k, m, missingValue) {
        var valid = missingValue || is_null(v) || (is_array(v) ? !v.length : (is_object(v) ? !Object.keys(v).length : !String(v).trim().length));
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', '') : "\""+k+"\" must be empty!");
        return valid;
    }

    v_maxitems(v, k, m, missingValue) {
        var valid = v.length <= this.inp;
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', this.inp) : "\""+k+"\" must have at most "+this.inp+" items!");
        return valid;
    }

    v_minitems(v, k, m, missingValue) {
        var valid = v.length >= this.inp;
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', this.inp) : "\""+k+"\" must have at least "+this.inp+" items!");
        return valid;
    }

    v_maxchars(v, k, m, missingValue) {
        var valid = v.length <= this.inp;
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', this.inp) : "\""+k+"\" must have at most "+this.inp+" characters!");
        return valid;
    }

    v_minchars(v, k, m, missingValue) {
        var valid = v.length >= this.inp;
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', this.inp) : "\""+k+"\" must have at least "+this.inp+" characters!");
        return valid;
    }

    async v_maxsize(v, k, m, missingValue) {
        var fs = false, valid = false;
        fs = await filesize(String(v));
        valid = false === fs ? false : fs <= this.inp;
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', this.inp) : "\""+k+"\" must have at most "+this.inp+" bytes!");
        return valid;
    }

    async v_minsize(v, k, m, missingValue) {
        var fs = false, valid = false;
        fs = await filesize(String(v));
        valid = false === fs ? false : fs >= this.inp;
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', this.inp) : "\""+k+"\" must have at least "+this.inp+" bytes!");
        return valid;
    }

    v_eq(v, k, m, missingValue) {
        var val = this.inp, valm = val, valid;
        if (val instanceof FormalField)
        {
            valm = !empty(this.msg) ? val.field : '"' + val.field + '"';
            val = m.get(val.field);
        }
        valid = val === v;
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', valm) : "\""+k+"\" must be equal to "+valm+"!");
        return valid;
    }

    v_neq(v, k, m, missingValue) {
        var val = this.inp, valm = val, valid;
        if (val instanceof FormalField)
        {
            valm = !empty(this.msg) ? val.field : '"' + val.field + '"';
            val = m.get(val.field);
        }
        valid = val != v;
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', valm) : "\""+k+"\" must not be equal to "+valm+"!");
        return valid;
    }

    v_gt(v, k, m, missingValue) {
        var val = this.inp, valm = val, valid;
        if (val instanceof FormalField)
        {
            valm = !empty(this.msg) ? val.field : '"' + val.field + '"';
            val = m.get(val.field);
        }
        valid = v > val;
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', valm) : "\""+k+"\" must be greater than "+valm+"!");
        return valid;
    }

    v_gte(v, k, m, missingValue) {
        var val = this.inp, valm = val, valid;
        if (val instanceof FormalField)
        {
            valm = !empty(this.msg) ? val.field : '"' + val.field + '"';
            val = m.get(val.field);
        }
        valid = v >= val;
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', valm) : "\""+k+"\" must be greater than or equal to "+valm+"!");
        return valid;
    }

    v_lt(v, k, m, missingValue) {
        var val = this.inp, valm = val, valid
        if (val instanceof FormalField)
        {
            valm = !empty(this.msg) ? val.field : '"' + val.field + '"';
            val = m.get(val.field);
        }
        valid = v < val;
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', valm) : "\""+k+"\" must be less than "+valm+"!");
        return valid;
    }

    v_lte(v, k, m, missingValue) {
        var val = this.inp, valm = val, valid;
        if (val instanceof FormalField)
        {
            valm = !empty(this.msg) ? val.field : '"' + val.field + '"';
            val = m.get(val.field);
        }
        valid = v <= val;
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', valm) : "\""+k+"\" must be less than or equal to "+valm+"}!");
        return valid;
    }

    v_between(v, k, m, missingValue) {
        var min = this.inp[0],
            max = this.inp[1],
            minm = min, maxm = max, valid;
        if (min instanceof FormalField)
        {
            minm = !empty(this.msg) ? min.field : '"' + min.field + '"';
            min = m.get(min.field);
        }
        if (max instanceof FormalField)
        {
            maxm = !empty(this.msg) ? max.field : '"' + max.field + '"';
            max = m.get(max.field);
        }
        valid = (min <= v) && (v <= max);
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', [minm, maxm].join(',')) : "\""+k+"\" must be between "+minm+" and "+maxm+"!");
        return valid;
    }

    v_in(v, k, m, missingValue) {
        var val = this.inp, valm, valid;
        if (val instanceof FormalField)
        {
            valm = !empty(this.msg) ? val.field : '"' + val.field + '"';
            val = m.get(val.field);
        }
        else
        {
            valm = !empty(this.msg) ? array(val).join(',') : '[' + array(val).join(',') + ']';
        }
        valid = -1 !== array(val).indexOf(v);
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', valm) : "\""+k+"\" must be one of "+valm+"!");
        return valid;
    }

    v_not_in(v, k, m, missingValue) {
        var val = this.inp, valm, valid;
        if (val instanceof FormalField)
        {
            valm = !empty(this.msg) ? val.field : '"' + val.field + '"';
            val = m.get(val.field);
        }
        else
        {
            valm = !empty(this.msg) ? array(val).join(',') : '[' + array(val).join(',') + ']';
        }
        valid = -1 === array(val).indexOf(v);
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', valm) : "\""+k+"\" must not be one of "+valm+"!");
        return valid;
    }

    v_match(v, k, m, missingValue) {
        var re = this.inp instanceof RegExp ? this.inp : (this.inp instanceof FormalDateTime ? this.inp.getPattern() : new RegExp(String(this.inp), '')),
            valid = re.test(String(v));
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', this.inp instanceof FormalDateTime ? this.inp.getFormat() : this.inp) : "\""+k+"\" must match " + (this.inp instanceof FormalDateTime ? '"' + this.inp.getFormat() + '"' : 'the') + " pattern!");
        return valid;
    }

    v_email(v, k, m, missingValue) {
        var valid = EMAIL_RE.test(String(v));
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', '') : "\""+k+"\" must be valid email pattern!");
        return valid;
    }

    v_url(v, k, m, missingValue) {
        var valid = URL_RE.test(String(v));
        if (!valid) throw new FormalException(!empty(this.msg) ? this.msg.replace('{key}', k).replace('{args}', '') : "\""+k+"\" must be valid url pattern!");
        return valid;
    }
}

class FormalError
{
    key = null;
    msg = '';

    constructor(msg = '', key = []) {
        this.msg = String(msg);
        this.key = key;
    }

    getMsg() {
        return this.msg;
    }

    getKey() {
        return this.key;
    }

    toString() {
        return this.msg;
    }
}

class Formal
{
    static VERSION = "1.0.0";

    // export these
    static Exception = FormalException;
    static Field = FormalField;
    static DateTime = FormalDateTime;
    static Type = FormalType;
    static Validator = FormalValidator;
    static Error = FormalError;

    static field(field) {
        return new FormalField(field);
    }

    static datetime(format, locale = null) {
        return new FormalDateTime(format, locale);
    }

    static typecast(type, args = null) {
        return new FormalType(type, args);
    }

    static validate(validator, args = null, msg = null) {
        return new FormalValidator(validator, args, msg);
    }

    opts = null;
    err = null;
    data = null;

    constructor() {
        this.opts = {};
        this.err = [];
        this.data = null;
        this
            .option('WILDCARD', '*')
            .option('SEPARATOR', '.')
            .option('break_on_first_error', false)
            .option('invalid_value_msg', 'Invalid Value in "{key}"!')
            .option('missing_value_msg', 'Missing Value in "{key}"!')
            .option('defaults', {})
            .option('typecasters', {})
            .option('validators', {})
        ;
    }

    option(key, val = null) {
        var nargs = arguments.length;
        if (1 == nargs)
        {
            return HAS.call(this.opts, key) ? this.opts[key] : undef;
        }
        else if (1 < nargs)
        {
            this.opts[key] = val;
        }
        return this;
    }

    async process(data) {
        var WILDCARD = this.option('WILDCARD'),
            SEPARATOR = this.option('SEPARATOR');
        this.data = null;
        this.err = [];
        data = clone(data);
        data = await this.doMergeDefaults(data, this.option('defaults'), WILDCARD, SEPARATOR);
        data = await this.doTypecast(data, this.option('typecasters'), [], [], WILDCARD, SEPARATOR);
        this.data = data;
        await this.doValidate(data, this.option('validators'), [], [], WILDCARD, SEPARATOR);
        this.data = null;
        return data;
    }

    getErrors() {
        return this.err;
    }

    get(field, _default = null, data = null) {
        if (null == data) data = this.data;
        var WILDCARD = this.option('WILDCARD'), SEPARATOR = this.option('SEPARATOR'),
            is_obj = is_object(data), is_arr = is_array(data), stack, result = null,
            to_get, o, key, p, i, l, k;
        if ((is_string(field) || is_numeric(field)) && (is_obj || is_arr))
        {
            stack = [[data, String(field)]];
            while (stack.length)
            {
                to_get = stack.pop();
                o = to_get[0];
                key = to_get[1];
                p = key.split(SEPARATOR);
                i = 0;
                l = p.length;
                while (i < l)
                {
                    k = p[i++];
                    if (i < l)
                    {
                        if (is_object(o))
                        {
                            if (WILDCARD === k)
                            {
                                result = [];
                                k = p.slice(i).join(SEPARATOR);
                                Object.keys(o).forEach(function(key) {
                                    stack.push([o, key+SEPARATOR+k]);
                                });
                                break;
                            }
                            else if (HAS.call(o, k))
                            {
                                o = o[k];
                            }
                        }
                        else if (is_array(o))
                        {
                            if (WILDCARD === k)
                            {
                                result = [];
                                k = p.slice(i).join(SEPARATOR);
                                Object.keys(o).forEach(function(key) {
                                    stack.push([o, key+SEPARATOR+k]);
                                });
                                break;
                            }
                            else if (HAS.call(o, k))
                            {
                                o = o[k];
                            }
                        }
                        else
                        {
                            return _default; // key does not exist
                        }
                    }
                    else
                    {
                        if (is_object(o))
                        {
                            if (WILDCARD === k)
                            {
                                result = Object.values(o);
                            }
                            else if (HAS.call(o, k))
                            {
                                if (is_array(result))
                                    result.push(o[k]);
                                else
                                    result = o[k];
                            }
                        }
                        else if (is_array(o))
                        {
                            if (WILDCARD === k)
                            {
                                result = o.slice();
                            }
                            else if (HAS.call(o, k))
                            {
                                if (is_array(result))
                                    result.push(o[k]);
                                else
                                    result = o[k];
                            }
                        }
                    }
                }
            }
            return result;
        }
        return _default;
    }

    doMergeKeys(keys, def) {
        var n = keys.length, defaults = def, i, o, k;
        for (i=n-1; i>=0; --i)
        {
            o = /*keys[i][1] ? [] :*/ {};
            k = keys[i]/*[0]*/;
            if (is_array(k))
            {
                k.forEach(function(kk) {
                    o[kk] = clone(defaults); // clone
                });
            }
            else
            {
                o[k] = defaults;
            }
            defaults = o;
        }
        return defaults;
    }

    async doMergeDefaults(data, defaults, WILDCARD = '*', SEPARATOR = '.') {
        if ((is_array(data) || is_object(data)) && (is_array(defaults) || is_object(defaults)))
        {
            var keys, key, def, k, kk, n, o, doMerge, i, ok;
            for (key in defaults)
            {
                if (!HAS.call(defaults, key)) continue;
                def = defaults[key];
                kk = key.split(SEPARATOR);
                n = kk.length;
                if (1 < n)
                {
                    o = data;
                    keys = [];
                    doMerge = true;
                    for (i=0; i<n; ++i)
                    {
                        k = kk[i];
                        if (WILDCARD === k)
                        {
                            ok = Object.keys(o);
                            if (!ok.length)
                            {
                                doMerge = false;
                                break;
                            }
                            keys.push(/*[*/ok/*, is_array(o)]*/);
                            o = o[ok[0]];
                        }
                        else if (HAS.call(o, k))
                        {
                            keys.push(/*[*/k/*, is_array(o)]*/);
                            o = o[k];
                        }
                        else if (i === n-1)
                        {
                            keys.push(/*[*/k/*, true]*/);
                        }
                        else
                        {
                            doMerge = false;
                            break;
                        }
                    }
                    if (doMerge)
                    {
                        data = await this.doMergeDefaults(data, this.doMergeKeys(keys, def), WILDCARD, SEPARATOR);
                    }
                }
                else
                {
                    if (HAS.call(data, key))
                    {
                        if ((is_array(data[key]) || is_object(data[key])) && (is_array(def) || is_object(def)))
                        {
                            data[key] = await this.doMergeDefaults(data[key], def, WILDCARD, SEPARATOR);
                        }
                        else if (is_null(data[key]) || (is_string(data[key]) && !data[key].trim().length))
                        {
                            data[key] = clone(def); // clone
                        }
                    }
                    else
                    {
                        data[key] = clone(def); // clone
                    }
                }
            }
        }
        else if (is_null(data[key]) || (is_string(data) && !data.trim().length))
        {
            data = clone(defaults); // clone
        }
        return data;
    }

    async doTypecast(data, typecaster, key = [], root = [], WILDCARD = '*', SEPARATOR = '.') {
        if (typecaster instanceof FormalType)
        {
            var n = key.length, i = 0, k, rk, ok, j, m, KEY;
            if (i < n)
            {
                k = key[i++];
                if ('' === k)
                {
                    return data;
                }
                else if (WILDCARD === k)
                {
                    if (i < n)
                    {
                        rk = key.slice(i);
                        root = root.concat(key.slice(0, i-1));
                        ok = Object.keys(data);
                        for (j=0,m=ok.length; j<m; ++j)
                        {
                            data[ok[j]] = await this.doTypecast(data[ok[j]], typecaster, rk, root.concat(array(ok[j])), WILDCARD, SEPARATOR);
                        }
                    }
                    else
                    {
                        root = root.concat(key.slice(0, i-1));
                        ok = Object.keys(data);
                        for (j=0,m=ok.length; j<m; ++j)
                        {
                            data = await this.doTypecast(data, typecaster, array(ok[j]), root, WILDCARD, SEPARATOR);
                        }
                    }
                    return data;
                }
                else if (HAS.call(data, k))
                {
                    rk = key.slice(i);
                    root = root.concat(key.slice(0, i));
                    data[k] = await this.doTypecast(data[k], typecaster, rk, root, WILDCARD, SEPARATOR);
                }
                else
                {
                    return data;
                }
            }
            else
            {
                KEY = root.concat(key).join(SEPARATOR);
                data = await typecaster.exec(data, KEY, this);
            }
        }
        else if (is_object(typecaster) || is_array(typecaster))
        {
            var k;
            for (k in typecaster)
            {
                if (!HAS.call(typecaster, k)) continue;
                data = await this.doTypecast(data, typecaster[k], empty(key) ? k.split(SEPARATOR) : key.concat(k.split(SEPARATOR)), root, WILDCARD, SEPARATOR);
            }
        }
        return data;
    }

    async doValidate(data, validator, key = [], root = [], WILDCARD = '*', SEPARATOR = '.') {
        if (this.option('break_on_first_error') && this.err.length) return;
        if (validator instanceof FormalValidator)
        {
            var n = key.length, i = 0, k, rk, ok, j, m, KEY, KEY_, valid, err;
            while (i < n)
            {
                k = key[i++];
                if ('' === k)
                {
                    continue;
                }
                else if (WILDCARD === k)
                {
                    if (i < n)
                    {
                        rk = key.slice(i);
                        root = root.concat(key.slice(0, i-1));
                        ok = Object.keys(data);
                        for (j=0,m=ok.length; j<m; ++j)
                        {
                            await this.doValidate(data[ok[j]], validator, rk, root.concat(array(ok[j])), WILDCARD, SEPARATOR);
                        }
                    }
                    else
                    {
                        root = root.concat(key.slice(0, i-1));
                        ok = Object.keys(data);
                        for (j=0,m=ok.length; j<m; ++j)
                        {
                            await this.doValidate(data, validator, array(ok[j]), root, WILDCARD, SEPARATOR);
                        }
                    }
                    return;
                }
                else if (HAS.call(data, k))
                {
                    data = data[k];
                }
                else
                {
                    KEY_ = root.concat(key);
                    KEY = KEY_.join(SEPARATOR);
                    err = null;
                    try {
                        valid = await validator.exec(null, KEY, this, true);
                    } catch (e) {
                        if (e instanceof FormalException)
                        {
                            valid = false;
                            err = e.message;
                        }
                        else
                        {
                            throw e;
                        }
                    }
                    if (!valid)
                    {
                        this.err.push(new FormalError(empty(err) ? this.option('missing_value_msg').replace('{key}', KEY).replace('{args}', '') : err, KEY_));
                    }
                    return;
                }
            }

            KEY_ = root.concat(key);
            KEY = KEY_.join(SEPARATOR);
            err = null;
            try {
                valid = await validator.exec(data, KEY, this, false);
            } catch (e) {
                if (e instanceof FormalException)
                {
                    valid = false;
                    err = e.message;
                }
                else
                {
                    throw e;
                }
            }
            if (!valid)
            {
                this.err.push(new FormalError(empty(err) ? this.option('invalid_value_msg').replace('{key}', KEY).replace('{args}', '') : err, KEY_));
            }
        }
        else if (is_object(validator) || is_array(validator))
        {
            var k;
            for (k in validator)
            {
                if (!HAS.call(validator, k)) continue;
                await this.doValidate(data, validator[k], empty(key) ? k.split(SEPARATOR) : key.concat(k.split(SEPARATOR)), root, WILDCARD, SEPARATOR);
            }
        }
    }
}

// export it
return Formal;
});
