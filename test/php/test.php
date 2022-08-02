<?php
include(dirname(__FILE__).'/../../src/php/Formal.php');

function test()
{
    $formdata = [
        'foo' => '',
        'moo' => [
                ['choo' => 1],
                ['choo' => 2],
                ['choo' => 3],
        ],

        'koo' => [
            '',
            '',
            '',
        ],

        'num' => [
            '0.1',
            '1.2',
        ],

        'date' => [
            '2012-11-02',
            '20-11-02',
        ],
    ];

    $formal = (new Formal())
            ->option('WILDCARD', '*') // default
            ->option('SEPARATOR', '.') // default
            ->option('break_on_first_error', false) // default
    ;
    $data = $formal
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
        ->process($formdata);
    $err = $formal->getErrors();

    print_r($formdata);

    print_r($data);

    echo implode("\n", $err);
}

test();
