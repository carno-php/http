<?php
/**
 * SW client http
 * User: moyo
 * Date: 2018/7/2
 * Time: 3:25 PM
 */

namespace Carno\HTTP\Powered\Swoole\Chips;

use Carno\HTTP\Standard\Response;
use Carno\HTTP\Standard\Streams\Form;
use Psr\Http\Message\RequestInterface as Request;
use Swoole\Http\Client as SWHClient;

trait Http
{
    /**
     * @param Request $request
     * @return array
     */
    private function http(Request $request) : array
    {
        /**
         * @var SWHClient $http
         */

        $http = $this->http;

        // -- request init

        $http->setMethod($request->getMethod());

        // -- net sender

        $executor = function ($fn) use ($http, $request) {
            $uri = $this->getUriPath($request->getUri());

            $stream = $request->getBody();

            if ($stream instanceof Form) {
                foreach ($stream->files() as $name => $file) {
                    $http->addFile($file->path(), $name);
                }
                $http->post($uri, $stream->data(), $fn);
                return;
            }

            if ($stream->getSize() > 0) {
                $http->setData((string)$stream);
            }

            $http->execute($uri, $fn);
        };

        // -- net receiver

        $receiver = function (SWHClient $c) use ($request) {
            $code = $c->statusCode;
            $headers = (array)$c->headers;
            $response = $c->body;

            // reset cli headers !!
            foreach (array_keys($c->headers ?? []) as $k) {
                unset($c->headers[$k]);
            }

            if ($code > 0) {
                return new Response($code, $headers, $response);
            }

            $this->throwing($request, $c);
        };

        // -- packing

        return [$executor, $receiver];
    }
}
