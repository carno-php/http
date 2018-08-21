<?php
/**
 * HTTP server by swoole
 * User: moyo
 * Date: 28/09/2017
 * Time: 5:30 PM
 */

namespace Carno\HTTP\Powered\Swoole;

use Carno\HTTP\Server\Connection;
use Carno\HTTP\Standard\ServerRequest;
use Carno\HTTP\Standard\Uri;
use Carno\Net\Address;
use Carno\Net\Contracts\HTTP;
use Carno\Net\Events;
use Carno\Serv\Powered\Swoole\ServerBase;
use Psr\Http\Message\ResponseInterface as Response;
use Swoole\Http\Server as SWHServer;
use Swoole\Http\Request as SWHRequest;
use Swoole\Http\Response as SWHResponse;

class Server extends ServerBase implements HTTP
{
    /**
     * @var array
     */
    protected $acceptEvs = ['request'];

    /**
     * @var array
     */
    protected $userConfig = [
        'http_parse_post' => false,
    ];

    /**
     * @var SWHServer
     */
    private $server = null;

    /**
     * @var SWHResponse[]
     */
    private $fds = [];

    /**
     * Server constructor.
     * @param string $serviced
     */
    public function __construct(string $serviced)
    {
        $this->serviced = $serviced;
    }

    /**
     * @param Address $address
     * @param Events $events
     * @param int $workers
     * @return HTTP
     */
    public function listen(Address $address, Events $events, int $workers) : HTTP
    {
        $this->server = $this->standardServerCreate(
            $address,
            $events,
            SWHServer::class,
            ['worker_num' => $workers]
        );
        return $this;
    }

    /**
     */
    public function serve() : void
    {
        $this->server->start();
    }

    /**
     */
    public function shutdown() : void
    {
        $this->server->shutdown();
    }

    /**
     * @param SWHRequest $request
     * @param SWHResponse $response
     */
    public function evRequest(SWHRequest $request, SWHResponse $response) : void
    {
        $raw = $request->rawContent();

        $headers = [];
        foreach ($request->header ?: [] as $name => $value) {
            $headers[$name] = strpos($value, ',') ? explode(',', $value) : $value;
        }

        $srq = new ServerRequest(
            $request->server,
            $request->cookie ?? [],
            $request->get ?? [],
            $request->server['request_method'],
            $headers,
            $raw ?: null
        );

        $host = $request->header['host'] ?? 'localhost';
        $port = null;

        strpos($host, ':') && list($host, $port) = explode(':', $host);

        $srq->withUri(
            new Uri(
                'http',
                $host,
                $port,
                $request->server['request_uri'] ?? '/',
                $request->server['query_string'] ?? ''
            )
        );

        $this->events->notify(
            Events\HTTP::REQUESTING,
            (new Connection)
                ->setID($this->setFdResponse($request->fd, $response))
                ->setRequest($srq)
                ->setLocal($this->server->host, $request->server['server_port'])
                ->setRemote($request->server['remote_addr'], $request->server['remote_port'])
                ->setServiced($this->serviced)
                ->from($this)
        );
    }

    /**
     * @param int $conn
     * @param Response $response
     * @return bool
     */
    public function reply(int $conn, Response $response) : bool
    {
        $replier = $this->getFdResponse($conn);

        if (is_null($replier) || !$this->server->exist($conn)) {
            return false;
        }

        $replier->status($response->getStatusCode());

        $headers = $response->getHeaders();
        foreach ($headers as $name => $values) {
            $replier->header($name, implode(',', $values));
        }

        $replier->end((string)$response->getBody());

        return true;
    }

    /**
     * @param int $conn
     * @return bool
     */
    public function close(int $conn) : bool
    {
        return $this->server->close($conn) ? true : false;
    }

    /**
     * @param int $fd
     * @param SWHResponse $response
     * @return int
     */
    private function setFdResponse(int $fd, SWHResponse $response) : int
    {
        $this->fds[$fd] = $response;
        return $fd;
    }

    /**
     * @param int $fd
     * @return SWHResponse
     */
    private function getFdResponse(int $fd) : ?SWHResponse
    {
        if (isset($this->fds[$fd])) {
            $resp = $this->fds[$fd];
            unset($this->fds[$fd]);
            return $resp;
        } else {
            return null;
        }
    }
}
