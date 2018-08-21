<?php
/**
 * HTTP Options (client side)
 * User: moyo
 * Date: 25/09/2017
 * Time: 11:13 AM
 */

namespace Carno\HTTP;

use Carno\HTTP\Client\Options\Followed;
use Carno\HTTP\Client\Options\Pooling;
use Carno\HTTP\Client\Options\Proxy;

class Options
{
    use Followed, Pooling, Proxy;

    /**
     * @var int
     */
    public $ttOverall = 0;

    /**
     * @var int
     */
    public $ttLookup = 0;

    /**
     * @var int
     */
    public $ttConnect = 0;

    /**
     * @var int
     */
    public $ttSend = 0;

    /**
     * @var int
     */
    public $ttWait = 0;

    /**
     * @param int $overall
     * @param int $lookup
     * @param int $connect
     * @param int $send
     * @param int $wait
     * @return static
     */
    public function setTimeouts(
        int $overall = 3500,
        int $lookup = 1000,
        int $connect = 500,
        int $send = 1000,
        int $wait = 2000
    ) : self {
        $this->ttOverall = $overall;

        $this->ttLookup = $lookup;
        $this->ttConnect = $connect;

        $this->ttSend = $send;
        $this->ttWait = $wait;

        return $this;
    }
}
