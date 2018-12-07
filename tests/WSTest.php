<?php
/**
 * WebSocket test
 * User: moyo
 * Date: 2018/10/19
 * Time: 9:24 PM
 */

namespace Carno\HTTP\Tests;

use function Carno\Coroutine\msleep;
use Carno\HTTP\Client;
use Carno\HTTP\Contracts\WSOpcode;

class WSTest extends Base
{
    public function testUpgrade()
    {
        $this->go(function () {
            /**
             * @var Client\Framing $framed
             */
            $framed = yield Client::upgrade('wss://echo.websocket.org');

            $this->assertTrue($framed->socket()->valid());

            yield $framed->message()->send(new Client\Frame('hello', WSOpcode::TEXT));

            /**
             * @var Client\Frame $resp
             */
            $resp = yield $framed->message()->recv();

            $this->assertEquals(WSOpcode::TEXT, $resp->code());
            $this->assertEquals('hello', $resp->data());

            $framed->socket()->close();

            yield msleep(500);

            $this->assertFalse($framed->socket()->valid());
            $this->assertFalse($framed->message()->closed()->pended());

            $this->assertNoGC();
        });
    }
}
