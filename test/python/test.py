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
            'num.*' : Formal.typecast('composite', [Formal.typecast('float'), Formal.typecast('clamp', [0.0, 1.0])
        ])}).option('validators', {
            'date.*' : Formal.validate('match', Formal.datetime('Y-m-d'), '"{key}" should match {args} !'),
            'date.0' : Formal.validate('eq', Formal.field('date.1'))
        }).process(formdata)

    err = formal.getErrors()

    print(json.dumps(formdata, indent=4))

    print(json.dumps(data, indent=4))

    print("\n".join(map(str, err)))


test()