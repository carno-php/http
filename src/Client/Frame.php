<?php
/**
 * Websocket frame
 * User: moyo
 * Date: 2018/6/27
 * Time: 12:18 PM
 */

namespace Carno\HTTP\Client;

use Carno\HTTP\Contracts\WSOpcode;

class Frame
{
    /**
     * @var int
     */
    private $code = null;

    /**
     * @var string
     */
    private $data = null;

    /**
     * Frame constructor.
     * @param string $data
     * @param int $code
     */
    public function __construct(string $data, int $code = WSOpcode::TEXT)
    {
        $this->code = $code;
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function code() : int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function data() : string
    {
        return $this->data;
    }
}
