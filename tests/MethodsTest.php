<?php
/**
 * Methods test
 * User: moyo
 * Date: 2018/9/7
 * Time: 1:24 PM
 */

namespace Carno\HTTP\Tests;

use Carno\HTTP\Client;
use Carno\HTTP\Exception\ErrorResponseException;
use Carno\HTTP\Standard\Streams\File;

class MethodsTest extends Base
{
    public function testAll()
    {
        $this->go(function () {
            /**
             * @var Client\Responding $resp
             */

            // simple case1

            $resp = yield Client::get('http://httpbin.org/status/200');

            $this->assertEmpty($resp->data());
            $this->assertEmpty($resp->payload());
            $this->assertEmpty((string)$resp);

            // simple case2

            $resp = yield Client::get('http://httpbin.org/status/404');

            $this->assertEmpty($resp->payload());

            $ec = 0;
            try {
                $resp->data();
            } catch (ErrorResponseException $e) {
                $ec = $e->getCode();
            }

            $this->assertEquals(404, $ec);

            // posting

            $target = 'http://httpbin.org/post';

            // posting 1-1

            $post = '{"hello":"world"}';
            $resp = yield Client::post($target, $post, ['Content-Type' => 'application/json']);
            $data = json_decode($resp->data(), true);
            $this->assertEquals($post, $data['data']);

            // posting 1-2

            $post = ['hello' => 'world'];
            $resp = yield Client::post($target, $post, ['Content-Type' => 'application/json']);
            $data = json_decode($resp->data(), true);
            $this->assertEquals($post, $data['json']);

            // posting 2-1

            $post = http_build_query($q = ['hello' => 'world']);
            $resp = yield Client::post($target, $post, [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]);
            $data = json_decode($resp->data(), true);
            $this->assertEquals($q, $data['form']);

            // posting 2-2

            $post = ['hello' => 'world'];
            $resp = yield Client::post($target, $post);
            $data = json_decode($resp->data(), true);
            $this->assertEquals($q, $data['form']);

            // file uploading

            $post = [
                'hello' => 'world',
                'world' => new File($f = __DIR__ . '/upload.txt'),
            ];
            $resp = yield Client::post($target, $post);
            $data = json_decode($resp->data(), true);
            $this->assertEquals(['hello' => 'world'], $data['form']);
            $this->assertEquals(['world' => file_get_contents($f)], $data['files']);
        });
    }
}
