<?php
/**
 * HTTP client by swoole
 * User: moyo
 * Date: 12/09/2017
 * Time: 3:07 PM
 */

namespace Carno\HTTP\Powered\Swoole;

use function Carno\Coroutine\await;
use Carno\HTTP\Contracts\Client as API;
use Carno\HTTP\Exception\RequestTimeoutException;
use Carno\HTTP\Options;
use Carno\HTTP\Powered\Swoole\Chips\Http;
use Carno\HTTP\Powered\Swoole\Chips\Socket;
use Carno\HTTP\Powered\Swoole\Chips\Throws;
use Carno\HTTP\Standard\Helper;
use Carno\Net\Address;
use Carno\Pool\Managed;
use Carno\Pool\Poolable;
use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Psr\Http\Message\RequestInterface as Request;
use Swoole\Http\Client as SWHClient;

class Client implements API, Poolable
{
    use Helper, Managed;
    use Http, Socket, Throws;

    /**
     * @var bool
     */
    private $keepalive = false;

    /**
     * @var Address
     */
    private $connect = null;

    /**
     * @var bool
     */
    private $closing = false;

    /**
     * @var SWHClient
     */
    private $http = null;

    /**
     * Client constructor.
     * @param Address $connect
     * @param bool $keepalive
     */
    public function __construct(Address $connect, bool $keepalive = false)
    {
        $this->connect = $connect;
        $this->keepalive = $keepalive;
    }

    /**
     * @param Request $request
     * @param Options $options
     */
    private function initialize(Request $request, Options $options) : void
    {
        $this->http = new SWHClient(
            $this->connect->host(),
            $this->connect->port(),
            in_array($scheme = $request->getUri()->getScheme(), ['https', 'wss'])
        );

        $this->http->set([
            'open_tcp_nodelay' => true,
            'keep_alive' => $this->keepalive || $socket = in_array($scheme, ['ws', 'wss']),
        ]);

        ($socket ?? null) && $this->http->set(['websocket_mask' => true]);

        if ($options->hasProxy()) {
            $px = $options->getProxy();
            $pxc = [
                'http_proxy_host' => $px['host'],
                'http_proxy_port' => $px['port'],
            ];
            $px['user'] && $pxc['http_proxy_user'] = $px['user'];
            $px['pass'] && $pxc['http_proxy_password'] = $px['pass'];
            $this->http->set($pxc);
        }

        $this->http->on('connect', function () {
            $this->http->on('error', [$this, 'failure']);
            $this->http->on('close', [$this, 'closing']);
        });
    }

    /**
     * @param string $ev
     */
    private function finalize(string $ev) : void
    {
        if (isset($this->http)) {
            unset($this->http);
            $this->disconnect();
        }
    }

    /**
     */
    private function disconnect() : void
    {
        $this->closed()->pended() && $this->closed()->resolve();
    }

    /**
     * @return Promised
     */
    public function connect() : Promised
    {
        return Promise::resolved();
    }

    /**
     * @return Promised
     */
    public function heartbeat() : Promised
    {
        return Promise::resolved();
    }

    /**
     * @return Promised
     */
    public function close() : Promised
    {
        if ($this->closing) {
            goto CLOSED_STA;
        }

        $this->closing = true;

        if (isset($this->http) && $this->http->isConnected()) {
            $this->http->close();
        } else {
            $this->disconnect();
        }

        CLOSED_STA:

        return $this->closed();
    }

    /**
     * @param SWHClient $c
     */
    public function failure(SWHClient $c = null) : void
    {
        $this->finalize('error');
    }

    /**
     * @param SWHClient $c
     */
    public function closing(SWHClient $c = null) : void
    {
        $this->finalize('close');
    }

    /**
     * @param Request $request
     * @param Options $options
     * @return Promised
     */
    public function execute(Request $request, Options $options) : Promised
    {
        if (is_null($this->http)) {
            $this->initialize($request, $options);
        }

        // config init -->

        $this->http->set(['timeout' => $options->ttOverall > 0 ? round($options->ttOverall / 1000, 3) : -1]);

        // host filter -->

        if ($request->hasHeader('Host') && filter_var($request->getHeaderLine('Host'), FILTER_VALIDATE_IP)) {
            $request->withoutHeader('Host');
        }

        // keepalive parser -->

        if (!$this->keepalive &&
            (!$request->hasHeader('Connection') ||
                ($request->hasHeader('Connection') &&
                    $request->getHeaderLine('Connection') !== 'close'
                )
            )
        ) {
            $request->withHeader('Connection', 'close');
        }

        // set headers -->

        $this->http->setHeaders($this->getHeaderLines($request));

        // programme init -->

        list($executor, $receiver) =
            $request->getMethod() === 'UPGRADE'
                ? $this->socket($request)
                : $this->http($request)
        ;

        // executing -->

        return await($executor, $receiver, $options->ttOverall, RequestTimeoutException::class, $request->getUri());
    }
}
