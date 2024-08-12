<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

Use \RightNow\UnitTest\Helper,
    \RightNow\Utils\Text,
    \RightNow\Api as Api,
    \RightNow\Utils\Url;

class OitTest extends CPTestCase {

    function testGetConfigs() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        //request with correct query parameter
        $response = $this->makeRequest(
            '/ci/oit/getConfigs?fields=answerUri,billingId,billingServiceHost,cachedContentServer,channelCachedContentServer,channelServiceEnabled,channelServiceHost,fileUploadMaxSize,interfaceId,interfaceName,launchPage,serviceHttpPort,servicePoolId,siteUrl,tenantName,tenantType,tenantVersion,userAbsentInterval,userAbsentRetryCount,validEmailPattern,videoClientScript,videoEnabled',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/json, text/javascript, */*; q=0.01'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Origin: http://testSite.devServer.oracle.com"));
        $this->assertTrue(Text::stringContains($response, "Access-Control-Allow-Credentials: true"));
        $this->assertTrue(Text::stringContains($response, "Vary: Origin"));
        $this->assertTrue(Text::stringContains($response, "answerUri"));
        $this->assertTrue(Text::stringContains($response, "billingId"));
        $this->assertTrue(Text::stringContains($response, "billingServiceHost"));
        $this->assertTrue(Text::stringContains($response, "cachedContentServer"));
        $this->assertTrue(Text::stringContains($response, "channelCachedContentServer"));
        $this->assertTrue(Text::stringContains($response, "channelServiceEnabled"));
        $this->assertTrue(Text::stringContains($response, "channelServiceHost"));
        $this->assertTrue(Text::stringContains($response, "fileUploadMaxSize"));
        $this->assertTrue(Text::stringContains($response, "interfaceId"));
        $this->assertTrue(Text::stringContains($response, "interfaceName"));
        $this->assertTrue(Text::stringContains($response, "launchPage"));
        $this->assertTrue(Text::stringContains($response, "serviceHttpPort"));
        $this->assertTrue(Text::stringContains($response, "servicePoolId"));
        $this->assertTrue(Text::stringContains($response, "siteUrl"));
        $this->assertTrue(Text::stringContains($response, "tenantName"));
        $this->assertTrue(Text::stringContains($response, "tenantType"));
        $this->assertTrue(Text::stringContains($response, "tenantVersion"));
        $this->assertTrue(Text::stringContains($response, "userAbsentInterval"));
        $this->assertTrue(Text::stringContains($response, "userAbsentRetryCount"));
        $this->assertTrue(Text::stringContains($response, "validEmailPattern"));
        $this->assertTrue(Text::stringContains($response, "videoClientScript"));
        $this->assertTrue(Text::stringContains($response, "videoEnabled"));

        //request with wrong query parameter
        $response = $this->makeRequest(
            '/ci/oit/getConfigs?keys=billingId,billingServiceHost,cachedContentServer,channelCachedContentServer,channelServiceEnabled,channelServiceHost,interfaceId,launchPage,servicePoolId,tenantName,tenantType,tenantVersion,videoClientScript,videoEnabled',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/json, text/javascript, */*; q=0.01'
		)
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "HTTP/1.1 400 Bad Request"));

        //request with domain not on whitelist
        $response = $this->makeRequest(
            '/ci/oit/getConfigs?fields=billingId,billingServiceHost,cachedContentServer,channelCachedContentServer,channelServiceEnabled,channelServiceHost,interfaceId,launchPage,servicePoolId,tenantName,tenantType,tenantVersion,videoClientScript,videoEnabled',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://rogueTestSite.com',
                    'Accept' => 'application/json, text/javascript, */*; q=0.01'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, " HTTP/1.1 404 Not Found"));
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => $oitCorsWhitelist), true);
    }

    private static function loadCurl() {
        static $curlInitialized;

        if (!isset($curlInitialized)) {
            if (!($curlInitialized = (extension_loaded('curl') || Api::load_curl()))) {
                exit("Unable to load cURL library");
            }
        }

        return $curlInitialized;
    }

    function doUpload($method = 'fileUpload', $fileContents = 'file attachment contents', $filename = '') {
        // Make a file in the log dir, since we can write there
        if (!$filename)
            $filename = 'cp' . getmypid() . '.txt';
        $logFile = Api::cfg_path() . '/log/' . $filename;
        file_put_contents($logFile, $fileContents);
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        $result = $this->sendFile("/ci/oit/fileUpload", $logFile);
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => $oitCorsWhitelist), true);
        @unlink($logFile);
        return $result;
    }

    function verifyUpload($response, $expectedName = null) {
        $this->assertIsA($response, 'stdClass');
        $this->assertNotNull($response->name);
        if ($expectedName) {
            $this->assertIdentical($expectedName, $response->name);
        }
        $this->assertNotNull($response->size);
        $this->assertIsA($response->size, 'int');
        $this->assertTrue($response->size > 0);
        $this->assertIsA($response->tmp_name, 'string');
        $this->assertFalse(property_exists($response, 'errorMessage'));
        $this->assertEqual(0, $response->error);
    }

    function sendFile($url, $fileLocation, $contentType = 'application/octet-stream') {
        self::loadCurl();
        $data = array(
            'file' => new \CURLFile($fileLocation, $contentType),
        );
        $headers = array('Origin: http://testSite.devServer.oracle.com','Host: ' . Rnow::getConfig(OE_WEB_SERVER), 'Cookie: cp_login_start=1;cp_session=' . get_instance()->sessionCookie);
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL            => Url::getShortEufBaseUrl() . $url,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_VERBOSE        => true,
            CURLOPT_HEADER         => true,
            CURLOPT_POSTFIELDS     => $data,
        ));
        $result = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($result, 0, $headerSize);
        $body = substr($result, $headerSize);

        curl_close($ch);
        return compact('body', 'headers');
    }

    function testUpload() {
        $response = $this->doUpload();
        $response = json_decode($response['body']);
        $this->verifyUpload($response);

        // replace matching < and >
        $filename = '<script>';
        $response = $this->doUpload('upload', 'bananas', $filename);
        $this->verifyUpload(json_decode($response['body']), str_replace(array('<', '>'), '-', $filename));

        // don't replace non-matching <
        $filename = '<script';
        $response = $this->doUpload('upload', 'bananas', $filename);
        $this->verifyUpload(json_decode($response['body']), $filename);

        // don't replace non-matching >
        $filename = 'script>';
        $response = $this->doUpload('upload', 'bananas', $filename);
        $this->verifyUpload(json_decode($response['body']), $filename);

        // replace matching &lt; and &gt;
        $filename = '&lt;script&gt;';
        $response = $this->doUpload('upload', 'bananas', $filename);
        $this->verifyUpload(json_decode($response['body']), str_replace(array('&lt;', '&gt;'), '-', $filename));

        // don't replace non-matching &lt;
        $filename = '&lt;script';
        $response = $this->doUpload('upload', 'bananas', $filename);
        $this->verifyUpload(json_decode($response['body']), $filename);

        // don't replace non-matching &gt;
        $filename = 'cpscript&gt;';
        $response = $this->doUpload('upload', 'bananas', $filename);
        $this->verifyUpload(json_decode($response['body']), $filename);

        // replace matching < and &gt;
        $filename = '<script&gt;';
        $response = $this->doUpload('upload', 'bananas', $filename);
        $this->verifyUpload(json_decode($response['body']), str_replace(array('<', '&gt;'), '-', $filename));

        // replace matching &lt; and >
        $filename = '&lt;script>';
        $response = $this->doUpload('upload', 'bananas', $filename);
        $this->verifyUpload(json_decode($response['body']), str_replace(array('&lt;', '>'), '-', $filename));

        // don't replace non-matching > and &lt;
        $filename = '>script&lt;';
        $response = $this->doUpload('upload', 'bananas', $filename);
        $this->verifyUpload(json_decode($response['body']), $filename);

        // don't replace non-matching &gt; and <
        $filename = '&gt;script<';
        $response = $this->doUpload('upload', 'bananas', $filename);
        $this->verifyUpload(json_decode($response['body']), $filename);

        // replace quote
        $filename = 'quote\'script';
        $response = $this->doUpload('upload', 'bananas', $filename);
        $this->verifyUpload(json_decode($response['body']), str_replace("'", '-', $filename));

        // encode double quote
        $filename = 'quote"script';
        $response = $this->doUpload('upload', 'bananas', $filename);
        $this->verifyUpload(json_decode($response['body']), str_replace('"', '%22', $filename));
    }

    function testFileUpload() {
        // Make a file in the log dir, since we can write there
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        $filename = 'cp' . getmypid() . '.txt';
        $logFile = Api::cfg_path() . '/log/' . $filename;
        file_put_contents($logFile, "Text to go into the text file.");

        $verifyUpload = function($response, $isError, $that) {
            $header = $response['headers'];
            $body = $response['body'];
            $that->assertStringContains($header, 'Content-Type: text/plain');
            $response = json_decode($body);
            $that->assertIsA($response, 'stdClass');
            if ($isError) {
                $that->assertEqual('This is a test error message', $response->errorMessage);
            }
            else {
                $that->assertNotNull($response->name);
                $that->assertSame('text/plain', $response->type);
                $that->assertNotNull($response->tmp_name);
                $that->assertSame(30, $response->size);
                $that->assertSame(0, $response->error);
            }
        };

        // verify good uploads are plain text in response
        $verifyUpload($this->sendFile("/ci/oit/fileUpload", $logFile, 'text/plain'), false, $this);
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => $oitCorsWhitelist), true);
        @unlink($logFile);
    }

    function testTempFileRequestFromInvalidDomain() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        $response = $this->makeRequest(
            '/ci/oit/getTempFile?fileSize=' . $fileUploaded->size . '&localFileName=' . $fileUploaded->tmp_name . '&contentType=' . $fileUploaded->type . '&userFileName=' . $fileUploaded->name . '&createdTime=2021-01-22T07:28:26.401Z',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://rogueTestSite.com',
                    'Accept' => 'application/json'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, " HTTP/1.1 404 Not Found"));
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => $oitCorsWhitelist), true);
    }

    function testTempFileRequestWithMissingParams() {
        $configArray = Helper::getConfigValues(array('OIT_CORS_ALLOWLIST'));
        $oitCorsWhitelist = $configArray['OIT_CORS_ALLOWLIST'];
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => '.*testSite.devServer.oracle.com'), true);
        $response = $this->makeRequest(
            '/ci/oit/getTempFile?localFileName=' . $fileUploaded->tmp_name . '&contentType=' . $fileUploaded->type . '&userFileName=' . $fileUploaded->name . '&createdTime=2021-01-22T07:28:26.401Z',
            array(
                'includeHeaders' => true,
                'headers' => array(
                    'origin' => 'http://testSite.devServer.oracle.com',
                    'Accept' => 'application/json'
                )
            )
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, " HTTP/1.1 400 Bad Request"));
        Helper::setConfigValues(array('OIT_CORS_ALLOWLIST' => $oitCorsWhitelist), true);
    }

    function testDocs() {
        $configArray = Helper::getConfigValues(array('CACHED_CONTENT_SERVER'));
        $cachedContentServer = $configArray['CACHED_CONTENT_SERVER'];
        Helper::setConfigValues(array('CACHED_CONTENT_SERVER' => 'testurl.com '), true);
        $response = $this->makeRequest('/ci/oit/docs');
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response, "site-url"));
        $this->assertTrue(Text::stringContains($response, "testurl.com"));
        $this->assertTrue(Text::stringContains($response, "OIT Registry"));
        Helper::setConfigValues(array('CACHED_CONTENT_SERVER' => $cachedContentServer), true);
    }
}
