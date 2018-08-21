<?php
/**
 * Request exception base
 * User: moyo
 * Date: 28/11/2017
 * Time: 9:57 AM
 */

namespace Carno\HTTP\Exception;

use Carno\Pool\Contracts\Broken;
use RuntimeException;

abstract class RequestException extends RuntimeException implements Broken
{

}
