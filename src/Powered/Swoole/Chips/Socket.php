<?php
/**
 * SW client socket
 * User: moyo
 * Date: 2018/7/2
 * Time: 3:25 PM
 */

namespace Carno\HTTP\Powered\Swoole\Chips;

use Carno\HTTP\Client\Frame;
use Carno\HTTP\Client\Framing;
use Carno\HTTP\Contracts\WSocket;
use Carno\HTTP\Contracts\WSOpcode;
use Carno\HTTP\Exception\ErrorResponseException;
use Carno\HTTP\Standard\Utils\CodePhrases;
use Psr\Http\Message\RequestInterface as Request;
use Swoole\Http\Client as SWHClient;
use Swoole\WebSocket\Frame as SWSFrame;

trait Socket
{
    /**
     * @param Request $request
     * @return array
     */
    private function socket(Request $request) : array
    {
        /**
         * @var SWHClient $http
         */

        $http = $this->http;

        // -- frame handler

        $framing = new Framing($socket = new class($http) implements WSocket {
            /**
             * @var SWHClient
             */
            private $c = null;

            /**
             * @var bool
             */
            private $v = false;

            /**
             * anonymous constructor.
             * @param SWHClient $c
             */
            public function __construct(SWHClient $c)
            {
                $this->c = $c;
            }

            /**
             * @param bool $sw
             */
            public function sv(bool $sw) : void
            {
                $this->v = $sw;
            }

            /**
             * @return bool
             */
            public function valid() : bool
            {
                return $this->v;
            }

            /**
             * @param Frame $frame
             */
            public function push(Frame $frame) : void
            {
                $this->c->push($frame->data(), $frame->code());
            }

            /**
             */
            public function close() : void
            {
                $this->push(new Frame(pack('n', 1000), WSOpcode::CLOSING));
            }
        });

        // -- message event

        $http->on('message', static function (SWHClient $c, SWSFrame $f) use ($framing, $socket) {
            switch ($f->opcode) {
                case WSOpcode::CLOSING:
                    $socket->sv(false);
                    $framing->message()->close();
                    return;
                default:
                    $framing->message()->send(new Frame($f->data, $f->opcode));
            }
        });

        $http->on('close', static function (SWHClient $c) use ($framing, $socket) {
            $socket->sv(false);
            $framing->message()->close();
        });

        // -- socket dial

        $connector = function ($fn) use ($http, $request) {
            $http->upgrade($this->getUriPath($request->getUri()), $fn);
        };

        // -- socket resp

        $response = function (SWHClient $c) use ($request, $framing, $socket) {
            $code = $c->statusCode;

            if ($code === 101) {
                $socket->sv(true);
                return $framing;
            }

            if ($code > 0) {
                throw new ErrorResponseException(CodePhrases::resolve($code), $code);
            }

            $this->throwing($request, $c);
        };

        // -- packing

        return [$connector, $response];
    }
}
