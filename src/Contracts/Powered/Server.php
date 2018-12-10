<?php
/**
 * HTTP server API
 * User: moyo
 * Date: 29/09/2017
 * Time: 12:50 PM
 */

namespace Carno\HTTP\Contracts\Powered;

use Carno\Net\Contracts\Conn;
use Carno\Net\Contracts\HTTP;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;

interface Server extends Conn
{
    /**
     * @param HTTP $chan
     * @return Server
     */
    public function from(HTTP $chan) : Server;

    /**
     * @return ServerRequest
     */
    public function request() : ServerRequest;

    /**
     * @param Response $response
     * @return bool
     */
    public function reply(Response $response) : bool;

    /**
     * @return bool
     */
    public function close() : bool;
}
