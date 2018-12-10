<?php
/**
 * HTTP Client
 * User: moyo
 * Date: 23/08/2017
 * Time: 10:52 PM
 */

namespace Carno\HTTP;

use function Carno\Coroutine\async;
use Carno\DNS\DNS;
use Carno\DNS\Result;
use Carno\HTTP\Client\Methods;
use Carno\HTTP\Contracts\Client as API;
use Carno\HTTP\Exception\ClientBindingException;
use Carno\HTTP\Powered\Swoole\Client as SWClient;
use Carno\Net\Address;
use Carno\Pool\Pool;
use Carno\Pool\Wrapper\SAR;
use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\UriInterface as Uri;
use Closure;

class Client implements API
{
    use SAR, Methods;

    /**
     * @var Options
     */
    private $options = null;

    /**
     * @var SWClient
     */
    private $session = null;

    /**
     * @var Address
     */
    private $restrict = null;

    /**
     * @var Promised
     */
    private $closed = null;

    /**
     * @var Pool
     */
    private $pool = null;

    /**
     * @var Result[]
     */
    private $dns = [];

    /**
     * @var Promised
     */
    private $ka = null;

    /**
     * Client constructor.
     * @param Options $options
     * @param Address $limited
     */
    public function __construct(Options $options, Address $limited = null)
    {
        $this->options = $options;
        $this->restrict = $limited;
    }

    /**
     * @return Address|null
     */
    public function restricted() : ?Address
    {
        return $this->restrict;
    }

    /**
     * @param Request $request
     * @param Promised|null $canceller
     * @return Promised
     */
    public function perform(Request $request, Promised $canceller = null) : Promised
    {
        if ($this->options->pooled()) {
            return async(function () use ($request, $canceller) {
                if ($canceller) {
                    $interrupt = Promise::deferred();
                    $canceller->then(static function () use ($interrupt) {
                        $interrupt->then(static function (SWClient $client) {
                            return $client->close();
                        });
                        $interrupt->resolve();
                    });
                }

                return yield $this->sarRun(
                    $this->pool($this->remote($request->getUri())),
                    'execute',
                    [$request, $this->options],
                    $interrupt ?? null
                );
            });
        }

        return async(function () use ($request, $canceller) {
            /**
             * @var SWClient $client
             */
            $this->session = $client = yield $this->http($this->remote($request->getUri()), false);

            $canceller && $canceller->then(static function () use ($client) {
                return $client->close();
            });

            return yield $client->execute($request, $this->options);
        });
    }

    /**
     * close pool connections or session client
     * @return Promised
     */
    public function close() : Promised
    {
        return
            ($this->pool
                ? $this->pool->shutdown()
                : (
                    $this->session
                        ? $this->session->close()
                        : Promise::resolved()
                )
            )->sync($this->closed())
        ;
    }

    /**
     * @return Promised
     */
    public function closed() : Promised
    {
        return $this->closed ?? $this->closed = Promise::deferred();
    }

    /**
     * @param Closure $initialize
     */
    public function keepalived(Closure $initialize) : void
    {
        ($this->ka ?? $this->ka = Promise::deferred())->then(function () use ($initialize) {
            $initialize($this->pool);
        });
    }

    /**
     * @param Address $remote
     * @return Pool
     */
    private function pool(Address $remote) : Pool
    {
        if ($this->pool) {
            return $this->pool;
        }

        $this->pool = new Pool($this->options->pooling(), function () use ($remote) {
            return $this->http($remote, true);
        }, $this->options->identify());

        $this->pool->closed()->then(function () {
            $this->pool = null;
        });

        $this->ka && $this->ka->resolve();

        return $this->pool;
    }

    /**
     * @param Address $remote
     * @param bool $pooled
     * @return SWClient
     */
    private function http(Address $remote, bool $pooled)
    {
        $host = $remote->host();
        $port = $remote->port();

        if ($this->restrict && ($this->restrict->host() !== $host || $this->restrict->port() !== $port)) {
            throw new ClientBindingException(
                sprintf(
                    'Expected "%s:%d" got "%s:%d"',
                    $this->restrict->host(),
                    $this->restrict->port(),
                    $host,
                    $port
                )
            );
        }

        $this->restrict = $remote;

        $dns = $this->dns[$host] ?? $this->dns[$host] = yield DNS::resolve($host, $this->options->ttLookup);

        return new SWClient(new Address($dns->random(), $port), $pooled);
    }

    /**
     * @param Uri $uri
     * @return Address
     */
    private function remote(Uri $uri) : Address
    {
        return $this->restrict ?? new Address($uri->getHost(), $uri->getPort());
    }
}
