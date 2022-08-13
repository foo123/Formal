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

        'soo' => [
                [
                    'boo' => 1,
                    'xoo' => 'a'
                ],
                [
                    'boo' => 2,
                    'xoo' => 'b'
                ],
                [
                    'boo' => 3,
                    'xoo' => 'c'
                ],
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

    echo implode("\n", $err) . PHP_EOL . PHP_EOL;

    var_dump($formal->get('soo.1.boo', 'default', $formdata));
    var_dump($formal->get('soo.*.boo', 'default', $formdata));
    var_dump($formal->get('soo.*.*', 'default', $formdata));
    var_dump($formal->get('soo.1.koo', 'default', $formdata));
    var_dump($formal->get('soo.*.koo', 'default', $formdata));
    var_dump($formal->get('soo.koo.1', 'default', $formdata));
    var_dump($formal->get('soo.koo.*', 'default', $formdata));
}

echo ('Formal::VERSION ' . Formal::VERSION . PHP_EOL);
test();
