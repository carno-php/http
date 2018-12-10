<?php
/**
 * HTTP client API
 * User: moyo
 * Date: 13/09/2017
 * Time: 5:21 PM
 */

namespace Carno\HTTP\Contracts\Powered;

use Carno\HTTP\Options;
use Carno\Net\Address;
use Carno\Promise\Promised;
use Psr\Http\Message\RequestInterface as Request;

interface Client
{
    /**
     * HTTP constructor.
     * @param Address $connect
     * @param bool $keepalive
     */
    public function __construct(Address $connect, bool $keepalive = false);

    /**
     * Promised actions:
     *  SUCCESS -> resolve(Carno\HTTP\Standard\Response)
     *  FAILED  -> throw(Exception)
     * @param Request $request
     * @param Options $options
     * @return Promised
     */
    public function execute(Request $request, Options $options) : Promised;
}
