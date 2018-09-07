<?php
/**
 * GC test
 * User: moyo
 * Date: 2018/9/7
 * Time: 1:16 PM
 */

namespace Carno\HTTP\Tests;

use Carno\HTTP\Client;
use Carno\HTTP\Options as HOptions;
use Carno\HTTP\Standard\Request;
use Carno\HTTP\Standard\Response;
use Carno\HTTP\Standard\Uri;
use Carno\Net\Address;
use Carno\Pool\Options as POptions;

class GCTest extends Base
{
    public function testNormal()
    {
        $this->go(function () {
            $web = new Address('httpbin.org', 80);

            $cli = new Client(new HOptions, $web);

            /**
             * @var Response $resp
             */

            $resp = yield $cli->perform(new Request('get', new Uri('http', $web->host(), $web->port(), '/status/201')));

            $this->assertEquals(201, $resp->getStatusCode());

            yield $cli->close();

            unset($resp, $cli);

            $this->assertNoGC();
        });
    }

    public function testPool()
    {
        $this->go(function () {
            $web = new Address('httpbin.org', 80);

            $cli = new Client((new HOptions)->keepalive(new POptions, 'gc-test'), $web);

            /**
             * @var Response $resp
             */

            $resp = yield $cli->perform(new Request('get', new Uri('http', $web->host(), $web->port(), '/status/202')));

            $this->assertEquals(202, $resp->getStatusCode());

            yield $cli->close();

            unset($resp, $cli);

            $this->assertNoGC();
        });
    }
}
