<?php
/**
 * Options of pooling
 * User: moyo
 * Date: 19/10/2017
 * Time: 3:19 PM
 */

namespace Carno\HTTP\Client\Options;

use Carno\Pool\Options;

trait Pooling
{
    /**
     * @var Options
     */
    private $pool = null;

    /**
     * @var string
     */
    private $identify = null;

    /**
     * @param Options $options
     * @param string $identify
     * @return static
     */
    public function keepalive(Options $options, string $identify = 'http') : self
    {
        $this->pool = $options;
        $this->identify = $identify;
        return $this;
    }

    /**
     * @return bool
     */
    public function pooled() : bool
    {
        return $this->pool ? true : false;
    }

    /**
     * @return Options
     */
    public function pooling() : Options
    {
        return $this->pool;
    }

    /**
     * @return string
     */
    public function identify() : string
    {
        return $this->identify;
    }
}
