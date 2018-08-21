<?php

namespace TESTS;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/assert.php';

use function Carno\Coroutine\go;
use Carno\HTTP\Client;
use Carno\HTTP\Exception\ErrorResponseException;
use Carno\HTTP\Standard\Streams\File;

go(static function () {
    /**
     * @var Client\Responding $resp
     */

    // simple case1

    $resp = yield Client::get('http://httpbin.org/status/200');

    assert($resp->data() === '');
    assert($resp->payload() === '');
    assert((string)$resp === '');

    // simple case2

    $resp = yield Client::get('http://httpbin.org/status/404');

    assert($resp->payload() === '');

    $ec = 0;
    try {
        $resp->data();
    } catch (ErrorResponseException $e) {
        $ec = $e->getCode();
    }

    assert($ec === 404);

    // posting

    $target = 'http://httpbin.org/post';

    // posting 1-1

    $post = '{"hello":"world"}';
    $resp = yield Client::post($target, $post, ['Content-Type' => 'application/json']);
    $data = json_decode($resp->data(), true);
    assert($data['data'] === $post);

    // posting 1-2

    $post = ['hello' => 'world'];
    $resp = yield Client::post($target, $post, ['Content-Type' => 'application/json']);
    $data = json_decode($resp->data(), true);
    assert($data['json'] === $post);

    // posting 2-1

    $post = http_build_query($q = ['hello' => 'world']);
    $resp = yield Client::post($target, $post, [
        'Content-Type' => 'application/x-www-form-urlencoded'
    ]);
    $data = json_decode($resp->data(), true);
    assert($data['form'] === $q);

    // posting 2-2

    $post = ['hello' => 'world'];
    $resp = yield Client::post($target, $post);
    $data = json_decode($resp->data(), true);
    assert($data['form'] === $q);

    // file uploading

    $post = [
        'hello' => 'world',
        'world' => new File($f = __DIR__ . '/upload.txt'),
    ];
    $resp = yield Client::post($target, $post);
    $data = json_decode($resp->data(), true);
    assert($data['form'] === ['hello' => 'world']);
    assert($data['files'] === ['world' => file_get_contents($f)]);
});
