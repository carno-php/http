<?php

namespace TESTS;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/assert.php';

use function Carno\Coroutine\go;
use Carno\HTTP\Client;
use Carno\HTTP\Options as HOptions;
use Carno\HTTP\Standard\Request;
use Carno\HTTP\Standard\Response;
use Carno\HTTP\Standard\Uri;
use Carno\Net\Address;
use Carno\Pool\Options as POptions;

go(function () {
    $web = new Address('httpbin.org', 80);

    $cli = new Client(new HOptions, $web);

    /**
     * @var Response $resp
     */

    $resp = yield $cli->perform(new Request('get', new Uri('http', $web->host(), $web->port(), '/status/201')));

    assert($resp->getStatusCode() === 201);

    yield $cli->close();

    unset($resp, $cli);
    assert(gc_collect_cycles() === 0);
});

go(static function () {
    $web = new Address('httpbin.org', 80);

    $cli = new Client((new HOptions)->keepalive(new POptions, 'gc-test'), $web);

    /**
     * @var Response $resp
     */

    $resp = yield $cli->perform(new Request('get', new Uri('http', $web->host(), $web->port(), '/status/202')));

    assert($resp->getStatusCode() === 202);

    yield $cli->close();

    unset($resp, $cli);
    assert(gc_collect_cycles() === 0);
});
