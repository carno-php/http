<?php
/**
 * Options of proxy
 * User: moyo
 * Date: 2018/4/2
 * Time: 8:32 PM
 */

namespace Carno\HTTP\Client\Options;

use Carno\HTTP\Exception\InvalidOptionsException;

trait Proxy
{
    /**
     * @var array
     */
    private $proxy = [];

    /**
     * @return bool
     */
    public function hasProxy() : bool
    {
        return ! empty($this->proxy);
    }

    /**
     * @return array
     */
    public function getProxy() : array
    {
        return $this->proxy;
    }

    /**
     * http://user:password@localhost:3128
     * @param string $dsn
     * @return static
     */
    public function setProxy(string $dsn) : self
    {
        switch (($parsed = parse_url($dsn))['scheme'] ?? 'http') {
            case 'http':
                $this->proxy = [
                    'host' => $parsed['host'] ?? '127.0.0.1',
                    'port' => $parsed['port'] ?? 80,
                    'user' => $parsed['user'] ?? null,
                    'pass' => $parsed['pass'] ?? null,
                ];
                break;
            default:
                throw new InvalidOptionsException(sprintf('Unsupported of proxy type "%s"', $parsed['scheme']));
        }
        return $this;
    }
}
