<?php
/**
 * Websocket
 * User: moyo
 * Date: 2018/6/27
 * Time: 12:20 PM
 */

namespace Carno\HTTP\Contracts;

use Carno\HTTP\Client\Frame;

interface WSocket
{
    /**
     * @return bool
     */
    public function valid() : bool;

    /**
     * @param Frame $frame
     */
    public function push(Frame $frame) : void;

    /**
     */
    public function close() : void;
}
