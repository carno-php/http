<?php
/**
 * HTTP client API
 * User: moyo
 * Date: 2018-12-08
 * Time: 21:58
 */

namespace Carno\HTTP\Contracts;

use Carno\Promise\Promised;
use Psr\Http\Message\RequestInterface as Request;

interface Client
{
    /**
     * @param Request $request
     * @param Promised|null $canceller
     * @return Promised
     */
    public function perform(Request $request, Promised $canceller = null) : Promised;

    /**
     * @return Promised
     */
    public function close() : Promised;

    /**
     * @return Promised
     */
    public function closed() : Promised;
}
