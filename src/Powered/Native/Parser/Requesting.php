<?php
/**
 * HTTP Decoder
 * User: moyo
 * Date: 08/01/2018
 * Time: 2:48 PM
 */

namespace Carno\HTTP\Powered\Native\Parser;

use Carno\HTTP\Exception\NVServerRequestingException;
use Carno\HTTP\Standard\ServerRequest;
use Carno\HTTP\Standard\Uri;

class Requesting
{
    private const BUFFER = 512;

    /**
     * @var Protocol
     */
    private $socket = null;

    /**
     * @var string
     */
    private $header = '';

    /**
     * @var string
     */
    private $body = '';

    /**
     * Decoder constructor.
     * @param Protocol $socket
     */
    public function __construct(Protocol $socket)
    {
        $this->socket = $socket;
    }

    /**
     * @return ServerRequest
     */
    public function getServerRequest() : ServerRequest
    {
        $this->partH();
        $this->partB();

        if (empty($this->header)) {
            throw new NVServerRequestingException;
        }

        list($method, $uri) = explode(
            ' ',
            substr($this->header, 0, $flp = strpos($this->header, Protocol::CRLF))
        );

        $qss = '';
        $qrs = [];
        if (false !== $qsp = strpos($uri, '?')) {
            parse_str($qss = substr($uri, $qsp), $qrs);
            $uri = substr($uri, 0, $qsp);
        }

        $headers = [];
        foreach (explode(Protocol::CRLF, substr($this->header, $flp + 2)) as $hl) {
            if (false !== $hsp = strpos($hl, ':')) {
                $headers[substr($hl, 0, $hsp)] = trim(substr($hl, $hsp + 1));
            }
        }

        $srq = new ServerRequest([], [], $qrs, $method, $headers, $this->body ?: null);

        $hosts = $srq->getHeader('host');
        $host = reset($hosts);
        if (strpos($host, ':')) {
            list($host, $port) = explode(':', $host);
        }

        $srq->withUri(new Uri('http', $host, $port ?? 80, $uri, $qss));

        return $srq;
    }

    /**
     */
    private function partH() : void
    {
        $got = $this->socket->read(self::BUFFER);

        if (false !== $eof = strpos($got, Protocol::SPLIT)) {
            $this->header .= substr($got, 0, $eof + 2);
            if ($eof + 4 < strlen($got)) {
                $this->body = substr($got, $eof + 4);
            }
            return;
        }

        if ($got) {
            $this->header .= $got;
            $this->partH();
        }
    }

    /**
     */
    private function partB() : void
    {
        // TODO support http post
    }
}
