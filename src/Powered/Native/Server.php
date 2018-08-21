<?php
/**
 * HTTP by stream-sock server
 * User: moyo
 * Date: 08/01/2018
 * Time: 2:26 PM
 */

namespace Carno\HTTP\Powered\Native;

use Carno\HTTP\Exception\NVServerCreatingException;
use Carno\HTTP\Powered\Native\Parser\Requesting;
use Carno\HTTP\Powered\Native\Parser\Responding;
use Carno\HTTP\Powered\Native\Parser\Protocol;
use Carno\HTTP\Server\Connection as HTTPConn;
use Carno\Net\Address;
use Carno\Net\Connection as NETConn;
use Carno\Net\Contracts\HTTP;
use Carno\Net\Events;
use Psr\Http\Message\ResponseInterface as Response;
use Throwable;

class Server implements HTTP
{
    /**
     * @var string
     */
    private $serviced = null;

    /**
     * @var resource
     */
    private $listener = null;

    /**
     * @var Events
     */
    private $events = null;

    /**
     * @var int
     */
    private $idx = 0;

    /**
     * @var resource[]
     */
    private $fds = [];

    /**
     * Server constructor.
     * @param string $serviced
     * @param Address $listen
     * @param Events $events
     */
    public function __construct(string $serviced, Address $listen, Events $events)
    {
        $this->serviced = $serviced;
        $this->events = $events;

        $err = $msg = null;

        if (false ===
            $this->listener = stream_socket_server(
                sprintf('tcp://%s:%d', $listen->host(), $listen->port()),
                $err,
                $msg
            )
        ) {
            throw new NVServerCreatingException($msg, $err);
        }

        $this->events->notify(
            Events\Server::STARTUP,
            (new NETConn)
                ->setServiced($this->serviced)
                ->setLocal($listen->host(), $listen->port())
        );
    }

    /**
     * startup http server
     */
    public function serve() : void
    {
        swoole_event_add($this->listener, function ($server) {
            $this->incoming($server);
        });
    }

    /**
     * shutdown http server
     */
    public function shutdown() : void
    {
        swoole_event_del($this->listener);
        stream_socket_shutdown($this->listener, STREAM_SHUT_RDWR);
    }

    /**
     * @param int $conn
     * @param Response $response
     * @return bool
     */
    public function reply(int $conn, Response $response) : bool
    {
        (new Responding(new Protocol($this->fds[$conn] ?? null)))->makeResponse($response);

        if ($response->getHeaderLine('Connection') === 'close') {
            $this->close($conn);
        }

        return true;
    }

    /**
     * @param int $conn
     * @return bool
     */
    public function close(int $conn) : bool
    {
        if ($fd = $this->fds[$conn] ?? null) {
            unset($this->fds[$conn]);
            swoole_event_del($fd);
            return fclose($fd);
        } else {
            return false;
        }
    }

    /**
     * @param $server
     */
    private function incoming($server) : void
    {
        $idx = $this->idx += 1;
        swoole_event_add($this->fds[$idx] = stream_socket_accept($server), function () use ($idx) {
            $this->receiving($idx);
        });
    }

    /**
     * @param int $idx
     */
    private function receiving(int $idx) : void
    {
        try {
            $srq = (new Requesting(new Protocol($this->fds[$idx] ?? null)))->getServerRequest();
            $this->events->notify(
                Events\HTTP::REQUESTING,
                (new HTTPConn)
                    ->setID($idx)
                    ->setRequest($srq)
                    ->setServiced($this->serviced)
                    ->from($this)
            );
        } catch (Throwable $e) {
            $this->close($idx);
        }
    }
}
