<?php
/**
 * HTTP Encoder
 * User: moyo
 * Date: 08/01/2018
 * Time: 4:04 PM
 */

namespace Carno\HTTP\Powered\Native\Parser;

use Psr\Http\Message\ResponseInterface;

class Responding
{
    private const LENGTH = 'Content-length';

    /**
     * @var string
     */
    private $socket = null;

    /**
     * Encoder constructor.
     * @param Protocol $socket
     */
    public function __construct(Protocol $socket)
    {
        $this->socket = $socket;
    }

    /**
     * @param ResponseInterface $response
     */
    public function makeResponse(ResponseInterface $response) : void
    {
        $lines[] = sprintf(
            'HTTP/%s %d %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        if (!$response->hasHeader(self::LENGTH)) {
            $response->withHeader(self::LENGTH, $response->getBody()->getSize());
        }

        foreach ($response->getHeaders() as $name => $values) {
            $lines[] = sprintf('%s: %s', $name, implode(',', $values));
        }

        $this->socket->write(implode(Protocol::CRLF, $lines).Protocol::SPLIT);

        if ($response->getBody()->getSize() > 0) {
            $this->socket->write((string)$response->getBody());
        }
    }
}
