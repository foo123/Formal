<?php

define('ROOT', dirname(__FILE__));
include(ROOT.'/../../tico/tico/Tico.php');

tico('http://localhost:8000', ROOT)
    ->option('webroot', ROOT)
    ->option('views', [tico()->path('/views')])
    ->option('case_insensitive_uris', true)
    ->set('formal', function() {
        include(ROOT.'/../src/php/Formal.php');
        return (new Formal());
    })
    ->on('*', '/', function() {

        $err = array();
        $data = array();
        if ('POST' === tico()->requestMethod())
        {
            $data = tico()->get('formal')
                ->option('defaults', [
                    'foo' => 'bar'
                ])
                ->option('typecasters', [
                    'num.*' => Formal::typecast('composite', [Formal::typecast('float'), Formal::typecast('clamp', [0.0, 1.0])
                ])])
                ->option('validators', [
                    'date.*' => Formal::validate('match', Formal::datetime('Y-m-d')),
                    'date.0' => Formal::validate('eq', Formal::field('date.1'))
                ])
                ->process(tico()->request()->request->all())
            ;
            $err = tico()->get('formal')->getErrors();
        }
        tico()->output(
            array(
                'title' => 'Index',
                'data' => $data,
                'err' => $err
            ),
            'index.tpl.php'
        );

    })
    ->on(false, function() {

        tico()->output(
            array(),
            '404.tpl.php',
            array('StatusCode' => 404)
        );

    })
    ->serve()
;

exit;