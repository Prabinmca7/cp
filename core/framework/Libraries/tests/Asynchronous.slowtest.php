<?php
\RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Libraries\Asynchronous;

class AsynchronousTest extends CPTestCase {
    public $testingClass = 'RightNow\Libraries\Asynchronous';
    private $connections = array();

    function testRequest() {
        try {
            Asynchronous::request(array());
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
        try {
            Asynchronous::request(array('url' => ''));
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
        try {
            Asynchronous::request(array('url' => '://alskdjfalsdkjf'));
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }

        $generatedKeyIndex = 1;
        $returnKey = Asynchronous::request(array('url' => Url::getShortEufBaseUrl() . '/app/chat/chat_landing'));
        $this->assertSame('CPGeneratedKey' . $generatedKeyIndex++, $returnKey);
        $this->connections[$returnKey] = (object) array('expectToPass' => true);

        $key = 'MyOwnKey';
        $returnKey = Asynchronous::request(array('url' => Url::getShortEufBaseUrl() . '/app/chat/chat_landing', 'key' => $key));
        $this->assertSame($key, $returnKey);
        $this->connections[$returnKey] = (object) array('expectToPass' => true);

        $returnKey = Asynchronous::request(array('url' => Url::getShortEufBaseUrl(), 'method' => 'POST'));
        $this->assertSame('CPGeneratedKey' . $generatedKeyIndex++, $returnKey);
        $this->connections[$returnKey] = (object) array('expectToPass' => false);

        $returnKey = Asynchronous::request(array('url' => Url::getShortEufBaseUrl(), 'method' => 'post'));
        $this->assertSame('CPGeneratedKey' . $generatedKeyIndex++, $returnKey);
        $this->connections[$returnKey] = (object) array('expectToPass' => false);

        $returnKey = Asynchronous::request(array('url' => Url::getShortEufBaseUrl(), 'method' => 'post', 'data' => array('foo' => 'bar')));
        $this->assertSame('CPGeneratedKey' . $generatedKeyIndex++, $returnKey);
        $this->connections[$returnKey] = (object) array('expectToPass' => false);
    }

    function testGet() {
        $returnVal = Asynchronous::get();
        $this->assertSame(null, $returnVal);

        foreach ($this->connections as $key => $val) {
            $returnVal = Asynchronous::get($key);
            if ($val->expectToPass) {
                $this->assertIsA($returnVal, 'string');
                $this->assertFalse(Text::stringContains($returnVal, 'Received code'));
            }
            else {
                $this->assertIsA($returnVal, 'string');
                $this->assertTrue(Text::stringContains($returnVal, 'Received code'));
            }
        }
    }

    function testInvalidHost() {
        $key = Asynchronous::request(array('url' => Url::getShortEufBaseUrl() . '/app/home', 'host' => 'banana'));
        $this->assertIdentical("Received code 302 from the server. (HTTP/1.1 302 Found\r\n)", Asynchronous::get($key));
    }


    function testStartGetHttpRequest() {
        $startGetHttpRequest = $this->getMethod('startGetHttpRequest');
        try {
            $startGetHttpRequest(Url::getShortEufBaseUrl() . ":8000", "80");
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
        $info = stream_get_meta_data($startGetHttpRequest(Url::getShortEufBaseUrl(), "80"));
        $this->assertSame('tcp_socket', $info['stream_type']);
    }

    function getStartPostHttpRequest() {
        $startPostHttpRequest = $this->getMethod('startPostHttpRequest');
        try {
            $startPostHttpRequest(Url::getShortEufBaseUrl() . ":8000", 10, '');
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
        $info = stream_get_meta_data($startPostHttpRequest(Url::getShortEufBaseUrl(), 10, ''));
        $this->assertSame('tcp_socket', $info['stream_type']);
    }

    function testParseURL() {
        $parseURL = $this->getMethod('parseURL');
        $parsed = $parseURL(Url::getShortEufBaseUrl());
        $this->assertSame(80, $parsed['port']);
        $this->assertSame(3, count($parsed));
        $parsed = $parseURL(Url::getShortEufBaseUrl() . ":8000");
        $this->assertSame(8000, $parsed['port']);
        $this->assertSame(3, count($parsed));
    }

    function testBuildHeaderString() {
        $buildHeaderString = $this->getMethod('buildHeaderString');
        $result = $buildHeaderString(array('foo' => 'bar'));
        $this->assertSame("foo bar\r\n\r\n\r\n", $result);
        $result = $buildHeaderString(array('foo' => 'bar', 'banana: ' => 'no'));
        $this->assertSame("foo bar\r\nbanana:  no\r\n\r\n\r\n", $result);
    }
}

class HttpResponseParserTest extends UnitTestCase {
    function testStates() {
        $parser = new \RightNow\Libraries\HttpResponseParser;
        try {
            $parser->addLine('HTTP/1.1 404 NOT FOUND');
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
            $this->assertSame(404, $parser->getCode());
        }

        $parser = new \RightNow\Libraries\HttpResponseParser;
        try {
            $parser->addLine('Malformed√');
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
            $this->assertSame(0, $parser->getCode());
        }

        // normal
        $parser = new \RightNow\Libraries\HttpResponseParser;
        $parser->addLine('HTTP/1.1 200 OK');
        $this->assertSame(200, $parser->getCode());
        $parser->addLine($firstHeader = 'Header-Something: BANANA');
        $parser->addLine($secondHeader = 'Header-Another: hey');
        $headers = $parser->getHeaders();
        $this->assertSame($firstHeader . $secondHeader, $headers);
        $parser->addLine("\r\n");
        $parser->addLine($firstData = "Fitter");
        $parser->addLine($secondData = "Happier");
        $data = $parser->getData();
        $this->assertSame($firstData . $secondData, $data);
        $headers = $parser->getHeaders();
        $this->assertSame($firstHeader . $secondHeader, $headers);

        // chunked
    }

    function testInvalidChunked() {
        $parser = new \RightNow\Libraries\HttpResponseParser;
        $parser->addLine('HTTP/1.1 200 OK');
        $this->assertSame(200, $parser->getCode());
        $parser->addLine($chunkedHeader = 'Transfer-Encoding: chunked');
        $parser->addLine($firstHeader = 'Header-Something: BANANA');
        $parser->addLine($secondHeader = 'Header-Another: hey');
        $headers = $parser->getHeaders();
        $this->assertSame($chunkedHeader . $firstHeader . $secondHeader, $headers);
        $parser->addLine("\r\n");
        $parser->addLine('13');
        try {
            $parser->addLine($firstData = "Fitter");
        }
        catch (\Exception $e) {
            $this->expectException($e);
        }
    }

    function testChunked() {
        $parser = new \RightNow\Libraries\HttpResponseParser;
        $parser->addLine('HTTP/1.1 200 OK');
        $this->assertSame(200, $parser->getCode());
        $parser->addLine($chunkedHeader = 'Transfer-Encoding: chunked');
        $parser->addLine($firstHeader = 'Header-Something: BANANA');
        $parser->addLine($secondHeader = 'Header-Another: hey');
        $headers = $parser->getHeaders();
        $this->assertSame($chunkedHeader . $firstHeader . $secondHeader, $headers);
        $parser->addLine("\r\n");
        $parser->addLine('1e');
        $parser->addLine($firstData = "Fitter Happier More Productive");
        $parser->addLine('0');
        $this->assertFalse($parser->done());
        $parser->addLine("\r\n");
        $this->assertTrue($parser->done());
        $this->assertIdentical($firstData, $parser->getData());
    }

    function testTrimChunk() {
        $parser = new \RightNow\Libraries\HttpResponseParser;
        $parser->addLine('HTTP/1.1 200 OK');
        $this->assertSame(200, $parser->getCode());
        $parser->addLine($chunkedHeader = 'Transfer-Encoding: chunked');
        $parser->addLine("\r\n");
        $parser->addLine('7');
        $parser->addLine("Happy\r\n");
        $this->assertSame('Happy', $parser->getData());
    }

    function testChunkSize() {
        $parser = new \ReflectionClass('\RightNow\Libraries\HttpResponseParser');

        $state = $parser->getProperty('currentState');
        $state->setAccessible(true);

        $addLines = $parser->getMethod('addLines');
        $getData = $parser->getMethod('getData');

        $parser = $parser->newInstance();

        $addLines->invoke($parser, array(
            'HTTP/1.1 200 OK',
            'Transfer-Encoding: chunked',
            "\r\n",
            '1e',
        ));
        $this->assertSame(\RightNow\Libraries\HttpResponseParser::RECEIVE_CHUNKED_DATA, $state->getValue($parser));
        $this->assertSame('', $getData->invoke($parser));

        // Exact
        $addLines->invoke($parser, array(
            "Fitter Happier More Productive",
        ));
        $this->assertSame(\RightNow\Libraries\HttpResponseParser::RECEIVE_CHUNK_SIZE, $state->getValue($parser));
        $this->assertSame("Fitter Happier More Productive", $getData->invoke($parser));

        // Split across packets
        $addLines->invoke($parser, array(
            'a',
            'Fitter ',
        ));
        $this->assertSame(\RightNow\Libraries\HttpResponseParser::RECEIVE_CHUNKED_DATA, $state->getValue($parser));
        $this->assertSame("Fitter Happier More ProductiveFitter", $getData->invoke($parser));
        $addLines->invoke($parser, array(
            'Hap',
        ));
        $this->assertSame(\RightNow\Libraries\HttpResponseParser::RECEIVE_CHUNK_SIZE, $state->getValue($parser));
        $this->assertSame("Fitter Happier More ProductiveFitterHap", $getData->invoke($parser));

        // More than
        $addLines->invoke($parser, array(
            'a',
            'Fitter Happier More Productive',
        ));
        $this->assertSame(\RightNow\Libraries\HttpResponseParser::RECEIVE_CHUNK_SIZE, $state->getValue($parser));
        $this->assertSame("Fitter Happier More ProductiveFitterHapFitter Happier More Productive", $getData->invoke($parser));

        // End
        $addLines->invoke($parser, array(
            '0',
        ));
        $this->assertSame(\RightNow\Libraries\HttpResponseParser::RECEIVE_CHUNKED_FOOTER, $state->getValue($parser));
        $addLines->invoke($parser, array(
            "\n\n",
        ));
        $this->assertSame(\RightNow\Libraries\HttpResponseParser::DONE, $state->getValue($parser));
        $this->assertSame("Fitter Happier More ProductiveFitterHapFitter Happier More Productive", $getData->invoke($parser));
    }

    function testInvalidStatus() {
        $parser = new \RightNow\Libraries\HttpResponseParser;
        try {
            $parser->addLine('HTTP/1.1 202 ACCEPTED');
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
        $parser = new \RightNow\Libraries\HttpResponseParser;
        try {
            $parser->addLine('HTTP/1.1 404 NOT FOUND');
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
        $parser = new \RightNow\Libraries\HttpResponseParser;
        try {
            $parser->addLine('HTTP/1.1 200');
        }
        catch (\Exception $e) {
            $this->fail();
        }
    }
}
