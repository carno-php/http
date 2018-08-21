<?php
/**
 * Client framing ops (websocket)
 * User: moyo
 * Date: 2018/6/27
 * Time: 10:44 AM
 */

namespace Carno\HTTP\Client;

use Carno\Channel\Channel;
use Carno\HTTP\Contracts\WSocket;

class Framing
{
    /**
     * @var Channel
     */
    private $chan = null;

    /**
     * @var WSocket
     */
    private $socket = null;

    /**
     * Framing constructor.
     * @param WSocket $socket
     */
    public function __construct(WSocket $socket)
    {
        $this->socket = $socket;
        $this->chan = new Channel;
    }

    /**
     * @return WSocket
     */
    public function socket() : WSocket
    {
        return $this->socket;
    }

    /**
     * @return Channel
     */
    public function message() : Channel
    {
        return $this->chan;
    }
}
