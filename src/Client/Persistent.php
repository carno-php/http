<?php
/**
 * HTTP client persistent
 * User: moyo
 * Date: 2018/4/13
 * Time: 10:25 AM
 */

namespace Carno\HTTP\Client;

use Carno\HTTP\Client;
use Carno\HTTP\Options as HOptions;
use Carno\Net\Address;
use Carno\Pool\Options as POptions;
use Carno\Pool\Pool;

class Persistent
{
    /**
     * @var Client[]
     */
    private static $keeps = [];

    /**
     * @param string $host
     * @param int $port
     * @param array $headers
     * @param HOptions $options
     * @return Client
     */
    public static function assign(string $host, int $port, array $headers, HOptions $options = null) : Client
    {
        $pk = "{$host}:{$port}";

        if (isset(self::$keeps[$pk])) {
            return self::$keeps[$pk];
        }

        $hsl = array_change_key_case($headers, CASE_LOWER);

        if (strtolower($hsl['connection'] ?? '') === 'keep-alive') {
            self::$keeps[$pk] = $cli = new Client(
                ($options ?? (new HOptions)->setTimeouts())
                    ->keepalive(
                        new POptions(1, 128, 4, 0, 55, 15, 0, 2000, 1000),
                        sprintf('http:%s:%d', $host, $port)
                    ),
                new Address($host, $port)
            );

            $cli->keepalived(static function (Pool $pool) use ($pk) {
                $pool->closed()->then(function () use ($pk) {
                    unset(self::$keeps[$pk]);
                });
            });

            return $cli;
        }

        return new Client($options ?? new HOptions);
    }
}
