<?php
/**
 * Websocket opcode
 * User: moyo
 * Date: 2018/6/27
 * Time: 12:27 PM
 */

namespace Carno\HTTP\Contracts;

interface WSOpcode
{
    public const TEXT = 0x1;
    public const BINARY = 0x2;

    public const CLOSING = 0x8;
}
