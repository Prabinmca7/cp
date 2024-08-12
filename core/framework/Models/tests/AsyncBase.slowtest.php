<?php
\RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class AsyncBaseTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\AsyncBase';

    function testConstructor() {
        $model = new Faux(array());
        $this->assertTrue(method_exists($model, 'request'));
        try {
            $model->request('getSomething', 'foo');
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }

        $model = new Faux(array('getSomething'));
        try {
            $model->request('getAnotherThing');
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }

        $model = new Faux(array('getAnotherThing'));
        $request = $model->request('getAnotherThing');
        $this->assertSame('RightNow\Models\AsyncBaseRequest', get_class($request));
        $this->assertFalse($request->connectionMade);
        $this->assertFalse($request->responseReceived);
        $this->assertSame('', $request->url);

        $model = new Faux(array('returnSomething'));
        try {
            $model->request('returnSomething');
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
        $model = new Faux(array('returnURL'));
        $request = $model->request('returnURL');
        $this->assertSame('RightNow\Models\AsyncBaseRequest', get_class($request));
        $this->assertTrue($request->connectionMade);
        $this->assertFalse($request->responseReceived);
    }

    function testCallback() {
        $model = new Faux(array('returnUrlPlusCallback'));
        $request = $model->request('returnUrlPlusCallback');
        $response = $request->getResponse();
        $this->assertTrue(is_array($response));
        $this->assertTrue($response['called']);
        $this->assertFalse($response['arg']);

        $model = new Faux(array('returnUrlPlusCallbackPlusArg'));
        $request = $model->request('returnUrlPlusCallbackPlusArg');
        $response = $request->getResponse();
        $this->assertTrue(is_array($response));
        $this->assertTrue($response['called']);
        $this->assertTrue($response['arg']);

        $model = new Faux(array('returnUrlPlusLocalCallbackPlusArg'));
        $request = $model->request('returnUrlPlusLocalCallbackPlusArg');
        $response = $request->getResponse();
        $this->assertTrue(is_array($response));
        $this->assertTrue($response['called']);
        $this->assertTrue($response['arg']);
    }

    function testBadCallback() {
        $model = new Faux(array('callbackDoesntExist'));
        try {
            $model->request('callbackDoesntExist');
            $this->fail();
        }
        catch(\Exception $e) {
            $this->pass();
        }

        $model = new Faux(array('callbackIsntACallback'));
        try {
            $model->request('callbackIsntACallback');
            $this->fail();
        }
        catch(\Exception $e) {
            $this->pass();
        }
    }

    function testBadHost() {
        $model = new Faux(array('badHost'));
        try {
            $model->request('badHost');
        }
        catch (\Exception $e) {
            $this->fail();
        }
    }

    function testCacheKeyCheck() {
        $cache = new \RightNow\Models\MockAsyncBaseCache();
        $cache->returns('get', true);
        $cache->expectOnce('get', array('banana'));
        $cache->expectNever('set');

        $model = new Faux(array('returnUrlAndCacheKey'), $cache);
        $model->cacheKey = 'banana';
        $model->request('returnUrlAndCacheKey')->getResponse();
    }

    function testCacheKeySet() {
        $cache = new \RightNow\Models\MockAsyncBaseCache();
        $cache->returns('get', false);
        $cache->expectOnce('get', array('banana'));
        $cache->expectOnce('set', array('banana', '*'));

        $model = new Faux(array('returnUrlAndCacheKey'), $cache);
        $model->cacheKey = 'banana';
        $model->request('returnUrlAndCacheKey')->getResponse();
    }

    function testCacheSetting(){
        $model = new Faux(array('returnUrlAndCacheKey'));
        $model->cacheResult('foo', 'bar');
        $this->assertIdentical('bar', $model->checkCache('foo'));
        $model->cacheResult('foo', null);
        $this->assertNull($model->checkCache('foo'));
    }
}

class Faux extends \RightNow\Models\AsyncBase {
    public $cacheKey;

    private function getURL() {
        return \RightNow\Utils\Url::getShortEufBaseUrl() . '/' . microtime(true);
    }
    function getSomething() {}
    function getAnotherThing() { return ''; }
    function returnSomething() {
        return array('banana' => 'no');
    }
    function returnURL() {
        return array('url' => $this->getURL());
    }
    function callbackDoesntExist() {
        return array('url' => 'foo', 'callback' => 'nothing');
    }
    function callbackIsntACallback() {
        return array('url' => 'foo', 'callback' => true);
    }
    function returnUrlPlusCallback() {
        return array('url' => $this->getURL(), 'callback' => 'aCallback');
    }
    function returnUrlPlusCallbackPlusArg() {
        return array('url' => $this->getURL(), 'callback' => 'aCallback', 'params' => array(true));
    }
    function returnUrlPlusLocalCallbackPlusArg() {
        return array('url' => $this->getURL(), 'callback' => function($response, $arg = null) {
            return array('called' => true, 'arg' => $arg !== null);
        }, 'params' => array(true));
    }
    function aCallback($response, $arg = null) {
        return array('called' => true, 'arg' => $arg !== null);
    }
    function badHost() {
        return array('url' => $this->getURL(), 'host' => 'bananafoo');
    }
    function returnUrlAndCacheKey() {
        return array('url' => $this->getURL(), 'cacheKey' => $this->cacheKey);
    }
}

Mock::generate('\RightNow\Models\AsyncBaseCache', '\RightNow\Models\MockAsyncBaseCache');

