import os, sys

DIR = os.path.dirname(os.path.abspath(__file__))

def import_module(name, path):
    import imp
    try:
        mod_fp, mod_path, mod_desc  = imp.find_module(name, [path])
        mod = getattr( imp.load_module(name, mod_fp, mod_path, mod_desc), name )
    except ImportError as exc:
        mod = None
        sys.stderr.write("Error: failed to import module ({})".format(exc))
    finally:
        if mod_fp: mod_fp.close()
    return mod

# import the Formal.py (as a) module, probably you will want to place this in another dir/package
Formal = import_module('Formal', os.path.join(DIR, '../../src/python/'))
if not Formal:
    print ('Could not load the Formal Module')
    sys.exit(1)
else:
    pass


def test():
    import json

    formal = Formal().option('WILDCARD', '*').option('SEPARATOR', '.').option('break_on_first_error', False)

    formdata = {
        'foo' : '',
        'moo' : [
                {'choo' : 1},
                {'choo' : 2},
                {'choo' : 3},
        ],

        'soo' : [
                {
                    'boo' : 1,
                    'xoo' : 'a'
                },
                {
                    'boo' : 2,
                    'xoo' : 'b'
                },
                {
                    'boo' : 3,
                    'xoo' : 'c'
                },
        ],

        'koo' : [
            '',
            '',
            '',
        ],

        'num' : [
            '0.1',
            '1.2',
        ],

        'date' : [
            '2012-11-02',
            '20-11-02',
        ]
    }

    data = formal.option('defaults', {
            'foo' : 'bar',
            'moo.*.foo' : 'bar',
            'koo.*' : 'bar'
        }).option('typecasters', {
            'koo.*.foo' : Formal.typecast('str'),
            'num.*' : Formal.typecast('composite', [Formal.typecast('float'), Formal.typecast('clamp', [0.0, 1.0])
        ])}).option('validators', {
            'foo.*' : Formal.validate('required'),
            'foo.*.foo' : Formal.validate('required'),
            'moo.*.foo' : Formal.validate('required'),
            'koo.*.foo' : Formal.validate('optional', Formal.validate('required')),
            'date.*' : Formal.validate('match', Formal.datetime('Y-m-d'), '"{key}" should match {args} !'),
            'date.0' : Formal.validate('eq', Formal.field('date.1'))
        }).process(formdata)

    err = formal.getErrors()

    print(json.dumps(formdata, indent=4))

    print(json.dumps(data, indent=4))

    print("\n".join(map(str, err)))

    print(json.dumps(formal.get('soo.1.boo', 'default', formdata), indent=4))
    print(json.dumps(formal.get('soo.*.boo', 'default', formdata), indent=4))
    print(json.dumps(formal.get('soo.*.*', 'default', formdata), indent=4))
    print(json.dumps(formal.get('soo.1.koo', 'default', formdata), indent=4))
    print(json.dumps(formal.get('soo.*.koo', 'default', formdata), indent=4))
    print(json.dumps(formal.get('soo.koo.1', 'default', formdata), indent=4))
    print(json.dumps(formal.get('soo.koo.*', 'default', formdata), indent=4))


print('Formal.VERSION ' + Formal.VERSION)
test()