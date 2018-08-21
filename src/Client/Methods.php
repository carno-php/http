<?php
/**
 * Client static commands
 * User: moyo
 * Date: 06/12/2017
 * Time: 2:05 PM
 */

namespace Carno\HTTP\Client;

use Carno\HTTP\Exception\InvalidRequestException;
use Carno\HTTP\Exception\NonrecognitionPayloadException;
use Carno\HTTP\Exception\RequestException;
use Carno\HTTP\Exception\ResponseException;
use Carno\HTTP\Options;
use Carno\HTTP\Standard\Request;
use Carno\HTTP\Standard\Response;
use Carno\HTTP\Standard\Streams\Body;
use Carno\HTTP\Standard\Streams\Form;
use Carno\HTTP\Standard\Uri;
use Psr\Http\Message\StreamInterface;

trait Methods
{
    /**
     * @param string $url
     * @param array $headers
     * @param Options $options
     * @return Responding
     * @throws RequestException
     * @throws ResponseException
     */
    public static function get(string $url, array $headers = [], Options $options = null)
    {
        return self::request('GET', $url, $headers, null, $options);
    }

    /**
     * @param string $url
     * @param mixed $payload
     * @param array $headers
     * @param Options $options
     * @return Responding
     * @throws RequestException
     * @throws ResponseException
     */
    public static function post(string $url, $payload = null, array $headers = [], Options $options = null)
    {
        return self::request('POST', $url, $headers, self::p2stream($payload, $headers), $options);
    }

    /**
     * @param string $url
     * @param array $headers
     * @param Options $options
     * @return Responding
     * @throws RequestException
     * @throws ResponseException
     */
    public static function delete(string $url, array $headers = [], Options $options = null)
    {
        return self::request('DELETE', $url, $headers, null, $options);
    }

    /**
     * @param string $url
     * @param array $headers
     * @param Options $options
     * @return Framing
     * @throws RequestException
     * @throws ResponseException
     */
    public static function upgrade(string $url, array $headers = [], Options $options = null)
    {
        return self::request('UPGRADE', $url, self::f2headers($headers, ['connection']), null, $options);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param StreamInterface $stream
     * @param Options $options
     * @return Responding|Framing
     * @throws RequestException
     * @throws ResponseException
     */
    private static function request(
        string $method,
        string $url,
        array $headers = [],
        StreamInterface $stream = null,
        Options $options = null
    ) {
        $p = parse_url($url);

        $req = new Request(
            $method,
            $uri = new Uri(
                $p['scheme'] ?? 'http',
                $host = $p['host'] ?? 'localhost',
                $port = $p['port'] ?? null,
                $p['path'] ?? '/',
                $p['query'] ?? null,
                $p['fragment'] ?? null
            ),
            $headers,
            $stream
        );

        if (is_null($port) && is_null($port = $uri->getPort())) {
            throw new InvalidRequestException('Unknown port from url');
        }

        $got = yield Persistent::assign($host, $port, $headers, $options)->perform($req);

        return $got instanceof Response ? new Responding($got) : $got;
    }

    /**
     * @param mixed $input
     * @param array $headers
     * @return StreamInterface
     */
    private static function p2stream($input, array $headers) : StreamInterface
    {
        switch (gettype($input)) {
            case 'string':
                return new Body($input);
            case 'array':
                if ((array_change_key_case($headers, CASE_LOWER)['content-type'] ?? null) === 'application/json') {
                    return new Body(json_encode($input, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                }
                return new Form($input);
            case 'object':
                if ($input instanceof StreamInterface) {
                    return $input;
                }
                break;
            case 'NULL':
                return new Body('');
        }

        throw new NonrecognitionPayloadException;
    }

    /**
     * @param array $headers
     * @param array $removes
     * @return array
     */
    private static function f2headers(array $headers, array $removes) : array
    {
        foreach ($headers as $name => $val) {
            if (in_array(strtolower($name), array_change_key_case($removes, CASE_LOWER))) {
                unset($headers[$name]);
            }
        }
        return $headers;
    }
}
