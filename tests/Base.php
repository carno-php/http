<?php
/**
 * Test base
 * User: moyo
 * Date: 2018/9/7
 * Time: 1:17 PM
 */

namespace Carno\HTTP\Tests;

use function Carno\Coroutine\async;
use Closure;
use PHPUnit\Framework\TestCase;
use Throwable;

abstract class Base extends TestCase
{
    protected function go(Closure $closure) : void
    {
        async($closure)->catch(static function (Throwable $e) {
            echo 'FAILURE ', get_class($e), ' :: ', $e->getMessage(), PHP_EOL;
            echo $e->getTraceAsString();
            exit(1);
        });
        swoole_event_wait();
    }

    protected function assertNoGC() : void
    {
        if (!(extension_loaded('xdebug') && xdebug_code_coverage_started())) {
            $this->assertEquals(0, gc_collect_cycles());
        }
    }
}
