<?php

require __DIR__ . '/vendor/autoload.php';

$mock = Mockery::mock('Stripe\StripeClient');
$mock->shouldReceive('getService')
    ->with('paymentIntents')
    ->andReturn((object)['capture' => function() { return (object)['id' => 'pi_test', 'status' => 'succeeded']; }]);

$result = $mock->paymentIntents;
var_dump($result);
