<?php
/**
 * HTTP Server
 * User: moyo
 * Date: 28/09/2017
 * Time: 4:40 PM
 */

namespace Carno\HTTP;

use Carno\HTTP\Powered\Native\Server as NVServer;
use Carno\HTTP\Powered\Swoole\Server as SWServer;
use Carno\HTTP\Server\Connection;
use Carno\HTTP\Standard\Response;
use Carno\Net\Address;
use Carno\Net\Contracts\HTTP;
use Carno\Net\Events;
use Closure;
use Throwable;

class Server
{
    /**
     * @param Address $address
     * @param Events $events
     * @param int $workers
     * @param string $serviced
     * @return HTTP
     */
    public static function listen(
        Address $address,
        Events $events,
        int $workers,
        string $serviced = 'server'
    ) : HTTP {
        return (new SWServer($serviced))->listen($address, $events, $workers);
    }

    /**
     * @param Address $listen
     * @param Closure $processor
     * @param string $serviced
     * @return HTTP
     */
    public static function httpd(
        Address $listen,
        Closure $processor,
        string $serviced = 'server'
    ) : HTTP {
        return new NVServer(
            $serviced,
            $listen,
            (new Events)->attach(Events\HTTP::REQUESTING, function (Connection $conn) use ($processor) {
                try {
                    $processor($conn);
                } catch (Throwable $e) {
                    $conn->reply(new Response(500, [], $e->getTraceAsString()));
                }
            })
        );
    }
}
