<?php
/**
 * Socket RW
 * User: moyo
 * Date: 08/01/2018
 * Time: 2:51 PM
 */

namespace Carno\HTTP\Powered\Native\Parser;

class Protocol
{
    public const CRLF = "\r\n";
    public const SPLIT = self::CRLF.self::CRLF;

    /**
     * @var resource
     */
    private $fd = null;

    /**
     * Socket constructor.
     * @param $resource
     */
    public function __construct($resource)
    {
        $this->fd = $resource;
    }

    /**
     * @param int $size
     * @return string
     */
    public function read(int $size) : string
    {
        return stream_socket_recvfrom($this->fd, $size);
    }

    /**
     * @param string $data
     * @return int
     */
    public function write(string $data) : int
    {
        return stream_socket_sendto($this->fd, $data);
    }
}
