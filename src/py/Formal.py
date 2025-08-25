##
#   Formal
#   validate nested (form) data with built-in and custom rules for PHP, JavaScript, Python
#
#   @version 1.3.1
#   https://github.com/foo123/Formal
#
##

import math, re, functools, os.path

EMAIL_RE = re.compile(r'^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$')

URL_RE = re.compile('^(?!mailto:)(?:(?:http|https|ftp)://)(?:\\S+(?::\\S*)?@)?(?:(?:(?:[1-9]\\d?|1\\d\\d|2[01]\\d|22[0-3])(?:\\.(?:1?\\d{1,2}|2[0-4]\\d|25[0-5])){2}(?:\\.(?:[0-9]\\d?|1\\d\\d|2[0-4]\\d|25[0-4]))|(?:(?:[a-z\\u00a1-\\uffff0-9]+-?)*[a-z\\u00a1-\\uffff0-9]+)(?:\\.(?:[a-z\\u00a1-\\uffff0-9]+-?)*[a-z\\u00a1-\\uffff0-9]+)*(?:\\.(?:[a-z\\u00a1-\\uffff]{2,})))|localhost)(?::\\d{2,5})?(?:(/|\\?|#)[^\\s]*)?$', re.I)

def is_numeric(x):
    return not math.isnan(int(x))

def is_string(x):
    return isinstance(x, str)

def is_array(x):
    return isinstance(x, (list, tuple))

def is_object(x):
    return isinstance(x, dict)

def is_array_or_object(x):
    return isinstance(x, (list, tuple, dict))

def is_file(x):
    return os.path.isfile(str(x))

def is_callable(x):
    return callable(x)

def method_exists(o, m):
    return hasattr(o, m) and callable(getattr(o, m))

def array(a):
    return a if isinstance(a, list) else (list(a) if isinstance(a, tuple) else [a])

def array_keys(o):
    if isinstance(o, (list, tuple)): return list(map(str, range(0, len(o))))
    if isinstance(o, dict): return list(o.keys())
    return []

def array_values(o):
    if isinstance(o, list): return o[:]
    if isinstance(o, tuple): return list(o)
    if isinstance(o, dict): return list(o.values())
    return []

def array_key_exists(k, o):
    if isinstance(o, dict):
        return (str(k) in o)
    elif isinstance(o, (list, tuple)):
        try:
            k = int(k)
        except ValueError:
            return False
        return 0 <= k and k < len(o)
    return False

def key_value(k, o):
    if isinstance(o, dict):
        return o[str(k)]
    elif isinstance(o, (list, tuple)):
        return o[int(k)]
    return None

def set_key_value(k, v, o):
    if isinstance(o, (list, tuple)):
        k = int(k)
        l = len(o)
        if k >= l: o.extend([None for i in range(k+1-l)])
        o[k] = v
    elif isinstance(o, dict):
        o[str(k)] = v
    return o

def empty(x):
    return (x is None) or (x is False) or (0 == x) or ('' == x) or (isinstance(x, (dict, list, tuple)) and not len(x))

def is_null(x):
    return x is None

def esc_re(s):
    return re.escape(str(s))

def by_length_desc(a, b):
    return len(b)-len(a)

def get_alternate_pattern(alts):
    alts.sort(key=functools.cmp_to_key(by_length_desc))
    return '|'.join(map(esc_re, alts))

def clone(o):
    if isinstance(o, (list, tuple)):
        return [clone(x) for x in o]

    elif isinstance(o, dict):
        oo = {}
        for k in o: oo[k] = clone(o[k])
        return oo

    else:
        return o

class FormalException(Exception):
    pass

class FormalField:
    def val(v, m = None):
        return m.get(v.field) if isinstance(v, FormalField) and isinstance(m, Formal) else v

    def __init__(self, field):
        self.field = str(field)

class FormalDateTime:
    def __init__(self, format, locale = None):
        if not locale: locale = {
            'day_short' : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'day' : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'month_short' : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'month' : ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            'meridian' : {'am' : 'am', 'pm' : 'pm', 'AM' : 'AM', 'PM' : 'PM'},
            'timezone_short' : ['UTC'],
            'timezone' : ['UTC'],
            'ordinal' : {'ord' : {'1' : 'st', '2' : 'nd', '3' : 'rd'}, 'nth' : 'th'},
        }

        # (php) date formats
        # http://php.net/manual/en/function.date.php
        D = {
            # Day --
            # Day of month w/leading 0; 01..31
             'd': '(31|30|29|28|27|26|25|24|23|22|21|20|19|18|17|16|15|14|13|12|11|10|09|08|07|06|05|04|03|02|01)'
            # Shorthand day name; Mon...Sun
            ,'D': '(' + get_alternate_pattern(locale['day_short']) + ')'
            # Day of month; 1..31
            ,'j': '(31|30|29|28|27|26|25|24|23|22|21|20|19|18|17|16|15|14|13|12|11|10|9|8|7|6|5|4|3|2|1)'
            # Full day name; Monday...Sunday
            ,'l': '(' + get_alternate_pattern(locale['day']) + ')'
            # ISO-8601 day of week; 1[Mon]..7[Sun]
            ,'N': '([1-7])'
            # Ordinal suffix for day of month; st, nd, rd, th
            ,'S': '' # added below
            # Day of week; 0[Sun]..6[Sat]
            ,'w': '([0-6])'
            # Day of year; 0..365
            ,'z': '([1-3]?[0-9]{1,2})'

            # Week --
            # ISO-8601 week number
            ,'W': '([0-5]?[0-9])'

            # Month --
            # Full month name; January...December
            ,'F': '(' + get_alternate_pattern(locale['month']) + ')'
            # Month w/leading 0; 01...12
            ,'m': '(12|11|10|09|08|07|06|05|04|03|02|01)'
            # Shorthand month name; Jan...Dec
            ,'M': '(' + get_alternate_pattern(locale['month_short']) + ')'
            # Month; 1...12
            ,'n': '(12|11|10|9|8|7|6|5|4|3|2|1)'
            # Days in month; 28...31
            ,'t': '(31|30|29|28)'

            # Year --
            # Is leap year?; 0 or 1
            ,'L': '([01])'
            # ISO-8601 year
            ,'o': '(\\d{2,4})'
            # Full year; e.g. 1980...2010
            ,'Y': '([12][0-9]{3})'
            # Last two digits of year; 00...99
            ,'y': '([0-9]{2})'

            # Time --
            # am or pm
            ,'a': '(' + get_alternate_pattern([
                locale['meridian']['am'],
                locale['meridian']['pm']
            ]) + ')'
            # AM or PM
            ,'A': '(' + get_alternate_pattern([
                locale['meridian']['AM'],
                locale['meridian']['PM']
            ]) + ')'
            # Swatch Internet time; 000..999
            ,'B': '([0-9]{3})'
            # 12-Hours; 1..12
            ,'g': '(12|11|10|9|8|7|6|5|4|3|2|1)'
            # 24-Hours; 0..23
            ,'G': '(23|22|21|20|19|18|17|16|15|14|13|12|11|10|9|8|7|6|5|4|3|2|1|0)'
            # 12-Hours w/leading 0; 01..12
            ,'h': '(12|11|10|09|08|07|06|05|04|03|02|01)'
            # 24-Hours w/leading 0; 00..23
            ,'H': '(23|22|21|20|19|18|17|16|15|14|13|12|11|10|09|08|07|06|05|04|03|02|01|00)'
            # Minutes w/leading 0; 00..59
            ,'i': '([0-5][0-9])'
            # Seconds w/leading 0; 00..59
            ,'s': '([0-5][0-9])'
            # Microseconds; 000000-999000
            ,'u': '([0-9]{6})'

            # Timezone --
            # Timezone identifier; e.g. Atlantic/Azores, ...
            ,'e': '(' + get_alternate_pattern(locale['timezone']) + ')'
            # DST observed?; 0 or 1
            ,'I': '([01])'
            # Difference to GMT in hour format; e.g. +0200
            ,'O': '([+-][0-9]{4})'
            # Difference to GMT w/colon; e.g. +02:00
            ,'P': '([+-][0-9]{2}:[0-9]{2})'
            # Timezone abbreviation; e.g. EST, MDT, ...
            ,'T': '(' + get_alternate_pattern(locale['timezone_short']) + ')'
            # Timezone offset in seconds (-43200...50400)
            ,'Z': '(-?[0-9]{5})'

            # Full Date/Time --
            # Seconds since UNIX epoch
            ,'U': '([0-9]{1,8})'
            # ISO-8601 date. Y-m-d\\TH:i:sP
            ,'c': '' # added below
            # RFC 2822 D, d M Y H:i:s O
            ,'r': '' # added below
        }
        # Ordinal suffix for day of month; st, nd, rd, th
        lords = array_values(locale['ordinal']['ord'])
        lords.append(locale['ordinal']['nth'])
        D['S'] = '(' + get_alternate_pattern(lords) + ')'
        # ISO-8601 date. Y-m-d\\TH:i:sP
        D['c'] = D['Y']+'-'+D['m']+'-'+D['d']+'\\\\'+D['T']+D['H']+':'+D['i']+':'+D['s']+D['P']
        # RFC 2822 D, d M Y H:i:s O
        D['r'] = D['D']+',\\s'+D['d']+'\\s'+D['M']+'\\s'+D['Y']+'\\s'+D['H']+':'+D['i']+':'+D['s']+'\\s'+D['O']

        format = str(format);
        rex = ''
        for i in range(len(format)):
            f = format[i]
            rex += D[ f ] if f in D else esc_re(f)


        self.format = format
        self.pattern = re.compile('^' + rex + '$')

    def getFormat(self):
        return self.format

    def getPattern(self):
        return self.pattern

    def __str__(self):
        return str(self.pattern.pattern)


class FormalType:
    def __init__(self, type, args = None):
        if isinstance(type, FormalType):
            self.func = type.func
            self.inp = type.inp
        else:
            method = 't_' + str(type).strip().lower() if is_string(type) else None
            self.func = method if method and method_exists(self, method) else (type if is_callable(type) else None)
            self.inp = args

    def exec(self, v, k = None, m = None):
        if is_string(self.func):
            v = getattr(self, self.func)(v, k, m)
        elif is_callable(self.func):
            v = self.func(v, self.inp, k, m)
        return v

    def t_composite(self, v, k, m):
        types = array(self.inp)
        for i in range(len(types)):
            v = types[i].exec(v, k, m)
        return v

    #def t_fields(self, v, k, m):
    #    SEPARATOR = m.option('SEPARATOR')
    #    if is_object(v):
    #        for field in self.inp:
    #            type = self.inp[field]
    #            v[field] = type.exec(v[field] if field in v else None, field if empty(k) else k+SEPARATOR+field, m)
    #    elif is_array(v):
    #        for field in self.inp:
    #            type = self.inp[field]
    #            v[int(field)] = type.exec(v[int(field)] if field in array_keys(v) else None, field if empty(k) else k+SEPARATOR+field, m)
    #    return v
    #
    #def t_default(self, v, k, m, missingValue = False):
    #    defaultValue = self.inp
    #    if missingValue or is_null(v):
    #        v = defaultValue(k, m) if is_callable(defaultValue) else defaultValue
    #    return v

    def t_bool(self, v, k, m):
        # handle string representation of booleans as well
        if is_string(v) and len(v):
            vs = v.lower()
            return 'true' == vs or 'on' == vs or '1' == vs
        return bool(v)

    def t_int(self, v, k, m):
        return int(v)

    def t_float(self, v, k, m):
        return float(v)

    def t_str(self, v, k, m):
        return str(v)

    def t_min(self, v, k, m):
        min = FormalField.val(self.inp, m)
        return min if v < min else v

    def t_max(self, v, k, m):
        max = FormalField.val(self.inp, m)
        return max if v > max else v

    def t_clamp(self, v, k, m):
        min = FormalField.val(self.inp[0], m)
        max = FormalField.val(self.inp[1], m)
        return min if v < min else (max if v > max else v)

    def t_trim(self, v, k, m):
        return str(v).strip()

    def t_lower(self, v, k, m):
        return str(v).lower()

    def t_upper(self, v, k, m):
        return str(v).upper()

class FormalValidator:
    def __init__(self, validator, args = None, msg = None):
        if isinstance(validator, FormalValidator):
            self.func = validator.func
            self.inp = validator.inp
            self.msg = validator.msg if empty(msg) else msg
        else:
            method = 'v_' + str(validator).strip().lower() if is_string(validator) else None
            self.func = method if method and method_exists(self, method) else (validator if is_callable(validator) else None)
            self.inp = args
            self.msg = msg

    def _and_(self, validator):
        return FormalValidator('and', (self, validator))

    def _or_(self, validator):
        return FormalValidator('or', (self, validator))

    def _not_(self, msg = None):
        return FormalValidator('not', self, msg)

    def exec(self, v, k = None, m = None, missingValue = False):
        valid = True
        if is_string(self.func):
            valid = bool(getattr(self, self.func)(v, k, m, missingValue))
        elif is_callable(self.func):
            valid = bool(self.func(v, self.inp, k, m, missingValue, self.msg))
        return valid

    def v_and(self, v, k, m, missingValue):
        valid = self.inp[0].exec(v, k, m, missingValue) and self.inp[1].exec(v, k, m, missingValue)
        return valid

    def v_or(self, v, k, m, missingValue):
        msg1 = None
        msg2 = None
        valid1 = False
        valid2 = False
        try:
            valid1 = self.inp[0].exec(v, k, m, missingValue)
        except FormalException as e:
            valid1 = False
            msg1 = str(e)

        if not valid1:
            try:
                valid2 = self.inp[1].exec(v, k, m, missingValue)
            except FormalException as e:
                valid2 = False
                msg2 = str(e)

        valid = valid1 or valid2
        if not valid and (not empty(msg1) or not empty(msg2)): raise FormalException(msg2 if empty(msg1) else msg1)
        return valid

    def v_not(self, v, k, m, missingValue):
        try:
            valid = not (self.inp.exec(v, k, m, missingValue))
        except FormalException as e:
            valid = True
        if (not valid) and (not empty(self.msg)): raise FormalException(self.msg.replace('{key}', k).replace('{args}', ''))
        return valid

    def v_optional(self, v, k, m, missingValue):
        valid = True
        if not missingValue:
            valid = self.inp.exec(v, k, m, False)
        return valid

    def v_required(self, v, k, m, missingValue):
        valid = not missingValue and not is_null(v)
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', '') if not empty(self.msg) else "\""+k+"\" is required!")
        return valid

    #def v_fields(self, v, k, m, missingValue):
    #    if not is_object(v) and not is_array(v): return False
    #    SEPARATOR = m.option('SEPARATOR')
    #    for field in self.inp:
    #        validator = self.inp[field]
    #        if is_object(v):
    #            if not field in v:
    #                if not validator.exec(None, field if empty(k) else k+SEPARATOR+field, m, True):
    #                    return False
    #            else:
    #                if not validator.exec(v[field], field if empty(k) else k+SEPARATOR+field, m, missingValue)
    #                    return False
    #        elif is_array(v):
    #            if not field in array_keys(v):
    #                if not validator.exec(None, field if empty(k) else k+SEPARATOR+field, m, True):
    #                    return False
    #            else:
    #                if not validator.exec(v[int(field)], field if empty(k) else k+SEPARATOR+field, m, missingValue)
    #                    return False
    #    return True

    def v_numeric(self, v, k, m, missingValue):
        valid = is_numeric(v)
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', '') if not empty(self.msg) else "\""+k+"\" must be numeric value!")
        return valid

    def v_object(self, v, k, m, missingValue):
        valid = is_object(v)
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', '') if not empty(self.msg) else "\""+k+"\" must be an object!")
        return valid

    def v_array(self, v, k, m, missingValue):
        valid = is_array(v)
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', '') if not empty(self.msg) else "\""+k+"\" must be an array!")
        return valid

    def v_file(self, v, k, m, missingValue):
        valid = is_file(str(v))
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', '') if not empty(self.msg) else "\""+k+"\" must be a file!")
        return valid

    def v_empty(self, v, k, m, missingValue):
        valid = missingValue or is_null(v) or (not len(v) if is_array(v) or is_object(v) else not len(str(v).strip()))
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', '') if not empty(self.msg) else "\""+k+"\" must be empty!")
        return valid

    def v_maxitems(self, v, k, m, missingValue):
        cnt = FormalField.val(self.inp, m)
        valid = len(v) <= cnt
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', str(cnt)) if not empty(self.msg) else "\""+k+"\" must have at most "+str(cnt)+" items!")
        return valid

    def v_minitems(self, v, k, m, missingValue):
        cnt = FormalField.val(self.inp, m)
        valid = len(v) >= cnt
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', str(cnt)) if not empty(self.msg) else "\""+k+"\" must have at least "+str(cnt)+" items!")
        return valid

    def v_maxchars(self, v, k, m, missingValue):
        cnt = FormalField.val(self.inp, m)
        valid = len(v) <= cnt
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', str(cnt)) if not empty(self.msg) else "\""+k+"\" must have at most "+str(cnt)+" characters!")
        return valid

    def v_minchars(self, v, k, m, missingValue):
        cnt = FormalField.val(self.inp, m)
        valid = len(v) >= cnt
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', str(cnt)) if not empty(self.msg) else "\""+k+"\" must have at least "+str(cnt)+" characters!")
        return valid

    def v_maxsize(self, v, k, m, missingValue):
        cnt = FormalField.val(self.inp, m)
        fs = False
        try:
            fs = os.path.getsize(str(v))
        except OSError:
            fs = False
        valid = False if fs is False else (fs <= cnt)
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', str(cnt)) if not empty(self.msg) else "\""+k+"\" must have at most "+str(cnt)+" bytes!")
        return valid

    def v_minsize(self, v, k, m, missingValue):
        cnt = FormalField.val(self.inp, m)
        fs = False
        try:
            fs = os.path.getsize(str(v))
        except OSError:
            fs = False
        valid = False if fs is False else (fs >= cnt)
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', str(cnt)) if not empty(self.msg) else "\""+k+"\" must have at least "+str(cnt)+" bytes!")
        return valid

    def v_eq(self, v, k, m, missingValue):
        val = self.inp
        valm = val
        if isinstance(val, FormalField):
            valm = val.field if not empty(self.msg) else '"' + val.field + '"'
            val = m.get(val.field)
        valid = val == v
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', str(valm)) if not empty(self.msg) else "\""+k+"\" must be equal to "+str(valm)+"!")
        return valid

    def v_neq(self, v, k, m, missingValue):
        val = self.inp
        valm = val
        if isinstance(val, FormalField):
            valm = val.field if not empty(self.msg) else '"' + val.field + '"'
            val = m.get(val.field)
        valid = val != v
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', str(valm)) if not empty(self.msg) else "\""+k+"\" must not be equal to "+str(valm)+"!")
        return valid

    def v_gt(self, v, k, m, missingValue):
        val = self.inp
        valm = val
        if isinstance(val, FormalField):
            valm = val.field if not empty(self.msg) else '"' + val.field + '"'
            val = m.get(val.field)
        valid = v > val
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', str(valm)) if not empty(self.msg) else "\""+k+"\" must be greater than "+str(valm)+"!")
        return valid

    def v_gte(self, v, k, m, missingValue):
        val = self.inp
        valm = val
        if isinstance(val, FormalField):
            valm = val.field if not empty(self.msg) else '"' + val.field + '"'
            val = m.get(val.field)
        valid = v >= val
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', str(valm)) if not empty(self.msg) else "\""+k+"\" must be greater than or equal to "+str(valm)+"!")
        return valid

    def v_lt(self, v, k, m, missingValue):
        val = self.inp
        valm = val
        if isinstance(val, FormalField):
            valm = val.field if not empty(self.msg) else '"' + val.field + '"'
            val = m.get(val.field)
        valid = v < val
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', str(valm)) if not empty(self.msg) else "\""+k+"\" must be less than "+str(valm)+"!")
        return valid

    def v_lte(self, v, k, m, missingValue):
        val = self.inp
        valm = val
        if isinstance(val, FormalField):
            valm = val.field if not empty(self.msg) else '"' + val.field + '"'
            val = m.get(val.field)
        valid = v <= val
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', str(valm)) if not empty(self.msg) else "\""+k+"\" must be less than or equal to "+str(valm)+"}!")
        return valid

    def v_between(self, v, k, m, missingValue):
        min = self.inp[0]
        max = self.inp[1]
        minm = min
        maxm = max
        if isinstance(min, FormalField):
            minm = min.field if not empty(self.msg) else '"' + min.field + '"'
            min = m.get(min.field)
        if isinstance(max, FormalField):
            maxm = max.field if not empty(self.msg) else '"' + max.field + '"'
            max = m.get(max.field)
        valid = (min <= v) and (v <= max)
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', ','.join([str(minm), str(maxm)])) if not empty(self.msg) else "\""+k+"\" must be between "+str(minm)+" and "+str(maxm)+"!")
        return valid

    def v_in(self, v, k, m, missingValue):
        val = self.inp
        if isinstance(val, FormalField):
            valm = val.field if not empty(self.msg) else '"' + val.field + '"'
            val = m.get(val.field)
        else:
            valm = ','.join(map(str, array(val))) if not empty(self.msg) else '[' + ','.join(map(str, array(val))) + ']'
        valid = v in array(val)
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', str(valm)) if not empty(self.msg) else "\""+k+"\" must be one of "+str(valm)+"!")
        return valid

    def v_not_in(self, v, k, m, missingValue):
        val = self.inp
        if isinstance(val, FormalField):
            valm = val.field if not empty(self.msg) else '"' + val.field + '"'
            val = m.get(val.field)
        else:
            valm = ','.join(map(str, array(val))) if not empty(self.msg) else '[' + ','.join(map(str, array(val))) + ']'
        valid = v not in array(val)
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', str(valm)) if not empty(self.msg) else "\""+k+"\" must not be one of "+str(valm)+"!")
        return valid

    def v_match(self, v, k, m, missingValue):
        pat = FormalField.val(self.inp, m)
        rex = pat.getPattern() if isinstance(pat, FormalDateTime) else pat
        valid = bool(re.match(rex, str(v)))
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', pat.getFormat() if isinstance(pat, FormalDateTime) else str(pat)) if not empty(self.msg) else "\""+k+"\" must match " + (pat.getFormat() if isinstance(pat, FormalDateTime) else 'the') + " pattern!")
        return valid

    def v_email(self, v, k, m, missingValue):
        valid = bool(re.match(EMAIL_RE, str(v)))
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', '') if not empty(self.msg) else "\""+k+"\" must be valid email pattern!")
        return valid

    def v_url(self, v, k, m, missingValue):
        valid = bool(re.match(URL_RE, str(v)))
        if not valid: raise FormalException(self.msg.replace('{key}', k).replace('{args}', '') if not empty(self.msg) else "\""+k+"\" must be valid url pattern!")
        return valid

class FormalError:
    def __init__(self, msg = '', key = list()):
        self.msg = str(msg)
        self.key = key

    def getMsg(self):
        return self.msg

    def getKey(self):
        return self.key

    def __str__(self):
        return self.msg

class Formal:
    """
    Formal for Python,
    https://github.com/foo123/Formal
    """
    VERSION = "1.3.1"

    # export these
    Exception = FormalException
    Field = FormalField
    DateTime = FormalDateTime
    Type = FormalType
    Validator = FormalValidator
    Error = FormalError

    @staticmethod
    def field(field):
        return FormalField(field)

    @staticmethod
    def datetime(format, locale = None):
        return FormalDateTime(format, locale)

    @staticmethod
    def typecast(type, args = None):
        return FormalType(type, args)

    @staticmethod
    def validate(validator, args = None, msg = None):
        return FormalValidator(validator, args, msg)

    def __init__(self):
        self.opts = {}
        self.err = []
        self.data = None
        self.option('WILDCARD', '*').option('SEPARATOR', '.').option('break_on_first_error', False).option('invalid_value_msg', 'Invalid Value in "{key}"!').option('missing_value_msg', 'Missing Value in "{key}"!').option('defaults', {}).option('typecasters', {}).option('validators', {})

    def option(self, *args):
        nargs = len(args)
        if 1 == nargs:
            key = str(args[0])
            return self.opts[key] if key in self.opts else None
        elif 1 < nargs:
            key = str(args[0])
            val = args[1]
            self.opts[key] = val
        return self

    def process(self, data):
        WILDCARD = self.option('WILDCARD')
        SEPARATOR = self.option('SEPARATOR')
        self.data = None
        self.err = []
        data = clone(data)
        data = self.doMergeDefaults(data, self.option('defaults'), WILDCARD, SEPARATOR)
        data = self.doTypecast(data, self.option('typecasters'), [], [], WILDCARD, SEPARATOR)
        self.data = data
        self.doValidate(data, self.option('validators'), [], [], WILDCARD, SEPARATOR)
        self.data = None
        return data

    def getErrors(self):
        return self.err

    def get(self, field, _default = None, data = None):
        if data is None: data = self.data
        WILDCARD = self.option('WILDCARD')
        SEPARATOR = self.option('SEPARATOR')
        is_array_result = False
        is_result_set = False
        result = None
        if (is_string(field) or is_numeric(field)) and isinstance(data, (list, tuple, dict)):
            stack = [(data, str(field))]
            while len(stack):
                o, key = stack.pop(0)
                p = key.split(SEPARATOR)
                i = 0
                l = len(p)
                while i < l:
                    k = p[i]
                    i += 1
                    if i < l:
                        if isinstance(o, (list, tuple, dict)):
                            if WILDCARD == k:
                                is_array_result = True
                                k = SEPARATOR.join(p[i:])
                                for kk in array_keys(o):
                                    stack.append((o, kk+SEPARATOR+k))
                                break
                            elif array_key_exists(k, o):
                                o = key_value(k, o)
                            else:
                                break
                        else:
                            break
                    else:
                        if isinstance(o, (list, tuple, dict)):
                            if WILDCARD == k:
                                is_array_result = True
                                if not is_result_set: result = []
                                result += array_values(o)
                                is_result_set = True
                            elif array_key_exists(k, o):
                                if is_array_result:
                                    if not is_result_set: result = []
                                    result.append(key_value(k, o))
                                else:
                                    result = key_value(k, o)
                                is_result_set = True
                            else:
                                if is_array_result:
                                    if not is_result_set: result = []
                                    result.append(_default)
                                else:
                                    result = _default
                                is_result_set = True

            return result if is_result_set else _default
        return _default

    def doMergeKeys(self, keys, _def):
        defaults = _def
        for k in reversed(keys):
            o = {}
            if is_array(k):
                for kk in k:
                    o = set_key_value(kk, clone(defaults), o)
            else:
                o = set_key_value(k, clone(defaults), o)
            defaults = o
        return defaults

    def doMergeDefaults(self, data, defaults, WILDCARD = '*', SEPARATOR = '.'):
        import json
        if is_array_or_object(data) and is_array_or_object(defaults):
            for key in array_keys(defaults):
                _def = key_value(key, defaults)
                kk = key.split(SEPARATOR)
                n = len(kk)
                if 1 < n:
                    o = data
                    keys = []
                    doMerge = True
                    for i in range(n):
                        k = kk[i]
                        if WILDCARD == k:
                            ok = array_keys(o)
                            if not len(ok):
                                doMerge = False
                                break
                            keys.append(ok)
                            o = key_value(ok[0], o)
                        elif array_key_exists(k, o):
                            keys.append(k)
                            o = key_value(k, o)
                        elif i == n-1:
                            keys.append(k)
                        else:
                            doMerge = False
                            break
                    if doMerge:
                        data = self.doMergeDefaults(data, self.doMergeKeys(keys, _def), WILDCARD, SEPARATOR)
                else:
                    if array_key_exists(key, data):
                        data_key = key_value(key, data)
                        if is_array_or_object(data_key) and is_array_or_object(_def):
                            data = set_key_value(key, self.doMergeDefaults(data_key, _def, WILDCARD, SEPARATOR), data)
                        elif is_null(data_key) or (is_string(data_key) and not len(data_key.strip())):
                            data = set_key_value(key, clone(_def), data)
                    else:
                        data = set_key_value(key, clone(_def), data)
        elif is_null(data) or (is_string(data) and not len(data.strip())):
            data = clone(defaults)

        return data

    def doTypecast(self, data, typecaster, key = list(), root = list(), WILDCARD = '*', SEPARATOR = '.'):
        if isinstance(typecaster, FormalType):
            n = len(key)
            i = 0
            if i < n:
                k = key[i]
                i += 1
                if '' == k:
                    return data
                elif WILDCARD == k:
                    if i < n:
                        kk = array_keys(data)
                        if len(kk):
                            rk = key[i:]
                            root = root + key[0:i-1]
                            for ok in kk:
                                data = set_key_value(ok, self.doTypecast(key_value(ok, data), typecaster, rk, root + [ok], WILDCARD, SEPARATOR), data)
                    else:
                        kk = array_keys(data)
                        if len(kk):
                            root = root + key[0:i-1]
                            for ok in kk:
                                data = self.doTypecast(data, typecaster, [ok], root, WILDCARD, SEPARATOR)
                    return data
                elif array_key_exists(k, data):
                    rk = key[i:]
                    root = root + key[0:i]
                    data = set_key_value(k, self.doTypecast(key_value(k, data), typecaster, rk, root, WILDCARD, SEPARATOR), data)
                else:
                    return data
            else:
                KEY = SEPARATOR.join(root + key)
                data = typecaster.exec(data, KEY, self)

        elif is_array_or_object(typecaster):
            for k in array_keys(typecaster):
                data = self.doTypecast(data, key_value(k, typecaster), k.split(SEPARATOR) if empty(key) else key + k.split(SEPARATOR), root, WILDCARD, SEPARATOR)

        return data

    def doValidate(self, data, validator, key = list(), root = list(), WILDCARD = '*', SEPARATOR = '.'):
        if self.option('break_on_first_error') and len(self.err): return
        if isinstance(validator, FormalValidator):
            n = len(key)
            i = 0
            while i < n:
                k = key[i]
                i += 1
                if '' == k:
                    continue
                elif WILDCARD == k:
                    if i < n:
                        kk = array_keys(data)
                        if not len(kk):
                            KEY_ = root + key
                            KEY = SEPARATOR.join(KEY_)
                            err = None
                            try:
                                valid = validator.exec(None, KEY, self, True)
                            except FormalException as e:
                                valid = False
                                err = str(e)
                            if not valid:
                                self.err.append(FormalError(self.option('missing_value_msg').replace('{key}', KEY).replace('{args}', '') if empty(err) else err, KEY_))
                            return
                        else:
                            rk = key[i:]
                            root = root + key[0:i-1]
                            for ok in kk:
                                self.doValidate(key_value(ok, data), validator, rk, root + [ok], WILDCARD, SEPARATOR)
                    else:
                        kk = array_keys(data)
                        if not len(kk):
                            KEY_ = root + key
                            KEY = SEPARATOR.join(KEY_)
                            err = None
                            try:
                                valid = validator.exec(None, KEY, self, True)
                            except FormalException as e:
                                valid = False
                                err = str(e)
                            if not valid:
                                self.err.append(FormalError(self.option('missing_value_msg').replace('{key}', KEY).replace('{args}', '') if empty(err) else err, KEY_))
                        else:
                            root = root + key[0:i-1]
                            for ok in kk:
                                self.doValidate(data, validator, [ok], root, WILDCARD, SEPARATOR)
                    return
                elif array_key_exists(k, data):
                    data = key_value(k, data)
                else:
                    KEY_ = root + key
                    KEY = SEPARATOR.join(KEY_)
                    err = None
                    try:
                        valid = validator.exec(None, KEY, self, True)
                    except FormalException as e:
                        valid = False
                        err = str(e)
                    if not valid:
                        self.err.append(FormalError(self.option('missing_value_msg').replace('{key}', KEY).replace('{args}', '') if empty(err) else err, KEY_))
                    return

            KEY_ = root + key
            KEY = SEPARATOR.join(KEY_)
            err = None
            try:
                valid = validator.exec(data, KEY, self, False)
            except FormalException as e:
                valid = False
                err = str(e)
            if not valid:
                self.err.append(FormalError(self.option('invalid_value_msg').replace('{key}', KEY).replace('{args}', '') if empty(err) else err, KEY_))

        elif is_array_or_object(validator):
            for k in array_keys(validator):
                self.doValidate(data, key_value(k, validator), k.split(SEPARATOR) if empty(key) else key + k.split(SEPARATOR), root, WILDCARD, SEPARATOR)


__all__ = ['Formal']
