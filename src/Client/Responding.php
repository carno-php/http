<?php
/**
 * Client responding operator
 * User: moyo
 * Date: 2018/5/30
 * Time: 3:49 PM
 */

namespace Carno\HTTP\Client;

use Carno\HTTP\Exception\ErrorResponseException;
use Carno\HTTP\Standard\Response;

class Responding
{
    /**
     * @var Response
     */
    private $response = null;

    /**
     * Responding constructor.
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return int
     */
    public function code() : int
    {
        return $this->response->getStatusCode();
    }

    /**
     * @return string
     */
    public function phrase() : string
    {
        return $this->response->getReasonPhrase();
    }

    /**
     * @param string $key
     * @return string
     */
    public function header(string $key) : ?string
    {
        return $this->response->hasHeader($key) ? $this->response->getHeaderLine($key) : null;
    }

    /**
     * raw response content
     * @return string
     */
    public function payload() : string
    {
        return (string) $this->response->getBody();
    }

    /**
     * parsed data with status checking
     * @return string
     * @throws ErrorResponseException
     */
    public function data() : string
    {
        $code = $this->response->getStatusCode();

        if ($code >= 400 && $code < 600) {
            throw new ErrorResponseException($this->response->getReasonPhrase(), $code);
        }

        return $this->payload();
    }

    /**
     * @deprecated
     * @return string
     */
    public function __toString() : string
    {
        return $this->payload();
    }
}
