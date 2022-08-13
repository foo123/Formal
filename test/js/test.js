"use strict";
var Formal = require('../../src/js/Formal.js');

async function test()
{
    var formal = (new Formal())
            .option('WILDCARD', '*') // default
            .option('SEPARATOR', '.') // default
            .option('break_on_first_error', false) // default
    ;
    var formdata = {
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
        ],
    };
    var data = await formal
        .option('defaults', {
            'foo' : 'bar',
            'moo.*.foo' : 'bar',
            'koo.*' : 'bar'
        })
        .option('typecasters', {
            'num.*' : Formal.typecast('composite', [Formal.typecast('float'), Formal.typecast('clamp', [0.0, 1.0])
        ])})
        .option('validators', {
            'date.*' : Formal.validate('match', Formal.datetime('Y-m-d'), '"{key}" should match {args} !'),
            'date.0' : Formal.validate('eq', Formal.field('date.1'))
        })
        .process(formdata);
    var err = formal.getErrors();

    console.log(JSON.stringify(formdata, null, 4));

    console.log(JSON.stringify(data, null, 4));

    console.log(err.join("\n"));

    console.log(JSON.stringify(formal.get('soo.1.boo', 'default', formdata), null, 4));
    console.log(JSON.stringify(formal.get('soo.*.boo', 'default', formdata), null, 4));
    console.log(JSON.stringify(formal.get('soo.*.*', 'default', formdata), null, 4));
    console.log(JSON.stringify(formal.get('soo.1.koo', 'default', formdata), null, 4));
    console.log(JSON.stringify(formal.get('soo.*.koo', 'default', formdata), null, 4));
    console.log(JSON.stringify(formal.get('soo.koo.1', 'default', formdata), null, 4));
    console.log(JSON.stringify(formal.get('soo.koo.*', 'default', formdata), null, 4));
}

console.log('Formal.VERSION ' + Formal.VERSION);
test();