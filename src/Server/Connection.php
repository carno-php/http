<?php
/**
 * HTTP server connection
 * User: moyo
 * Date: 29/09/2017
 * Time: 12:48 PM
 */

namespace Carno\HTTP\Server;

use Carno\HTTP\Contracts\Server;
use Carno\Net\Connection as NET;
use Carno\Net\Contracts\HTTP;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;

class Connection extends NET implements Server
{
    /**
     * @var HTTP
     */
    private $chan = null;

    /**
     * @var ServerRequest
     */
    private $request = null;

    /**
     * @param HTTP $chan
     * @return Server
     */
    public function from(HTTP $chan) : Server
    {
        $this->chan = $chan;
        return $this;
    }

    /**
     * @param ServerRequest $request
     * @return static
     */
    public function setRequest(ServerRequest $request) : self
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return ServerRequest
     */
    public function request() : ServerRequest
    {
        return $this->request;
    }

    /**
     * @param Response $response
     * @return bool
     */
    public function reply(Response $response) : bool
    {
        return $this->chan->reply($this->id(), $response);
    }

    /**
     * @return bool
     */
    public function close() : bool
    {
        return $this->chan->close($this->id());
    }
}
