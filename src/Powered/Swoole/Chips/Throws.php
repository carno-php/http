<?php
/**
 * SW client throws
 * User: moyo
 * Date: 2018/7/2
 * Time: 3:22 PM
 */

namespace Carno\HTTP\Powered\Swoole\Chips;

use Carno\HTTP\Exception\ConnectionRefusedException;
use Carno\HTTP\Exception\ConnectionTimeoutException;
use Carno\HTTP\Exception\RequestCancelledException;
use Carno\HTTP\Exception\RequestException;
use Carno\HTTP\Exception\RequestFailedException;
use Carno\HTTP\Exception\RequestInterruptedException;
use Carno\HTTP\Exception\RequestTimeoutException;
use Carno\HTTP\Exception\ResponseException;
use Carno\HTTP\Exception\UnknownResponseException;
use Psr\Http\Message\RequestInterface as Request;
use Swoole\Http\Client as SWHClient;

trait Throws
{
    /**
     * @param Request $request
     * @param SWHClient $c
     * @throws RequestException
     * @throws ResponseException
     */
    private function throwing(Request $request, SWHClient $c) : void
    {
        $url = (string) $request->getUri();

        switch ($c->statusCode) {
            case -1:
                switch ($c->errCode) {
                    case 61:
                        throw new ConnectionRefusedException($url); // darwin kernel
                    case 110:
                        throw new ConnectionTimeoutException($url);
                    case 111:
                        throw new ConnectionRefusedException($url);
                }
                throw new RequestFailedException(sprintf('#%d::%s', $c->errCode, $url));
            case -2:
                throw new RequestTimeoutException($url);
            case -3:
                throw $this->closing
                    ? new RequestCancelledException($url)
                    : new RequestInterruptedException($url);
            default:
                throw new UnknownResponseException($url);
        }
    }
}
