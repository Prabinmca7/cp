<?php

use RightNow\Api,
    RightNow\Utils\Text,
    RightNow\Utils\Framework,
    RightNow\Utils\Url,
    RightNow\Utils\Connect as ConnectUtils,
    RightNow\Connect\v1_4 as Connect,
    RightNow\UnitTest\Helper as TestHelper;

TestHelper::loadTestedFile(__FILE__);

class FattachTest extends CPTestCase {
    public $testingClass = 'RightNow\Controllers\Fattach';
    protected $hookEndpointClass = __CLASS__;
    protected $hookEndpointFilePath = __FILE__;
    protected $attachmentAnswer = null;

    function verifyHeaders($response) {
        $directives = array(
            'Date: ',
            'Accept-Ranges: none',
            'Content-Type: ',
        );
        foreach ($directives as $directive) {
            if (!Text::stringContains($response, $directive)) {
                $this->fail("Directive not found: $directive");
            }
        }

    }

    function verifyErrorRedirect($response, $errorCode) {
        $this->assertStatusCode($response, "302 Moved Temporarily");
        if ($errorCode === 4) {
            $this->assertTrue(Text::stringContains($response, "Location: /app/error/error_id/$errorCode [following]"));
        }
    }

    function doUpload($method = 'upload', $fileContents = 'file attachment contents', $count = 1, $filename = '') {
        // Make a file in the log dir, since we can write there
        if (!$filename)
            $filename = 'cp' . getmypid() . '.txt';
        $logFile = \RightNow\Api::cfg_path() . '/log/' . $filename;
        file_put_contents($logFile, $fileContents);
        $result = $this->sendFileWithConstraints("/ci/fattach/$method", $logFile, $count, 'text/plain');
        @unlink($logFile);
        return $result;
    }

    // borrwed from Helper, additions specific to file attach upload widget
    function sendFileWithConstraints($url, $fileLocation, $count = 1, $contentType = 'text/plain') {
        self::loadCurl();

        $data = array(
            'file' => new \CURLFile($fileLocation, $contentType),
            'name' => 'Test_1',
            'count' => $count,
            'path' => 'Test',
            'max_attachments' => '3',
            'max_attach_hash' => Framework::getSHA2Hash(get_instance()->session->getSessionData('sessionID') . '3' . strrev(get_instance()->session->getSessionData('sessionID'))),
            'constraints' => Framework::createPostToken(json_encode(array('upload_Test_1' => Rnow::getConfig(VALID_FILE_EXTENSIONS))), 'Test', '/ci/fattach/upload'),
            'f_tok' => Framework::createTokenWithExpiration(0),
            'fAttachFormToken' => Framework::createTokenWithExpiration(0, false)
        );

        $headers = array('Host: ' . Rnow::getConfig(OE_WEB_SERVER), 'Cookie: cp_login_start=1;cp_session=' . get_instance()->sessionCookie);

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
    
    function sendFileWithoutFtok($url, $fileLocation, $count = 1, $contentType = 'text/plain') {
        self::loadCurl();

        $data = array(
            'file' => new \CURLFile($fileLocation, $contentType),
            'name' => 'Test_1',
            'count' => $count,
            'path' => 'standard/Test',
            'max_attachments' => '3',
            'max_attach_hash' => Framework::getSHA2Hash(get_instance()->session->getSessionData('sessionID') . '3' . strrev(get_instance()->session->getSessionData('sessionID'))),
            'constraints' => Framework::createPostToken(json_encode(array('upload_Test_1' => Rnow::getConfig(VALID_FILE_EXTENSIONS))), 'Test', '/ci/fattach/upload'),
            'fAttachFormToken' => Framework::createTokenWithExpiration(0, false)
        );

        $headers = array('Host: ' . Rnow::getConfig(OE_WEB_SERVER), 'Cookie: cp_login_start=1;cp_session=' . get_instance()->sessionCookie);

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

    private static function loadCurl() {
        static $curlInitialized;

        if (!isset($curlInitialized)) {
            if (!($curlInitialized = (extension_loaded('curl') || Api::load_curl()))) {
                exit("Unable to load cURL library");
            }
        }

        return $curlInitialized;
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

    function createAttachment($content, $fileName = null, $contentType = "text/plain") {
        $ans = new Connect\Answer();
        $ans->FileAttachments = new Connect\FileAttachmentAnswerArray();
        $fattach = new Connect\FileAttachmentAnswer();
        $fattach->ContentType = $contentType;
        $fp = $fattach->makeFile();
        fwrite( $fp, $content );
        fclose( $fp );
        $fattach->FileName = $fileName ?: "NewFile.txt";
        $ans->FileAttachments[] = $fattach;
        $ans->AccessLevels[] = 1; //everyone
        $ans->StatusWithType->Status->ID = 4; //public
        $ans->AnswerType->ID = 1;
        $ans->Language->ID = 1;
        $ans->Summary = "The summary of the answer";
        $ans->save();
        // Force a commit so we can access it via the controller
        Connect\ConnectAPI::commit();
        $this->attachmentAnswer = $ans;
        return array($ans->FileAttachments[0]->ID, $fattach);
    }

    function destroyAttachment() {
        if($this->attachmentAnswer) {
            $this->destroyObject($this->attachmentAnswer);
        }
    }

    function testGet() {
        $response = $this->makeRequest("/ci/fattach/get/foo/bar", array('justHeaders' => true));
        $this->verifyErrorRedirect($response, 4);
        $response = $this->makeRequest("/ci/fattach/get/1/", array('justHeaders' => true));
        $this->verifyErrorRedirect($response, 4);
        $response = $this->makeRequest("/ci/fattach/get/30", array('justHeaders' => true));
        $this->verifyErrorRedirect($response, 3); // Was 4

        //Create an attachment
        $content = "This is the contents of a text file";
        list($attachmentID,) = $this->createAttachment($content);

        //Check if the attachment can be retrieved
        $response = $this->makeRequest("/ci/fattach/get/{$attachmentID}", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertFalse(Text::stringContains($response, "302 Moved Temporarily"));
        $this->assertTrue(Text::stringContains($response, "filename=\"NewFile.txt\""));
        $this->verifyHeaders($response);

        $response = $this->makeRequest("/ci/fattach/get/{$attachmentID}");
        $this->assertIsA($response, 'string');
        $this->assertEqual($response, $content);

        //Delete the attachment
        $this->destroyAttachment();

        //Create an HTML attachment, which should be encoded
        $content = '">"><script>alert(1)</script>';
        list($attachmentID,) = $this->createAttachment($content, null, 'text/html');

        $response = $this->makeRequest("/ci/fattach/get/{$attachmentID}");
        $this->assertIsA($response, 'string');
        $this->assertEqual($response, '&quot;&gt;&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;');

        //Delete the HTML attachment
        $this->destroyAttachment();
    }

    function testGetGetWithSpacesInFilename(){

        //Create an attachment
        $content = "This is the contents of a text file";
        list($attachmentID,) = $this->createAttachment($content, 'New File.txt');

        //Check if the attachment can be retrieved
        $response = $this->makeRequest("/ci/fattach/get/{$attachmentID}", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertStringContains($response, "filename=\"New File.txt\"");
        $this->verifyHeaders($response);

        //Delete the attachment
        $this->destroyAttachment();
    }

    function getGetWithSpacesInFilename(){

        //Create an attachment
        $content = "This is the contents of a text file";
        list($attachmentID,) = $this->createAttachment($content, 'New File.txt');

        //Check if the attachment can be retrieved
        $response = $this->makeRequest("/ci/fattach/get/{$attachmentID}", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertFalse(Text::stringContains($response, "302 Moved Temporarily"));
        $this->assertTrue(Text::stringContains($response, "filename=New+File.txt"));
        $this->verifyHeaders($response);

        //Delete the attachment
        $this->destroyAttachment();
    }

    function testGetHook() {
        //Create an attachment that we can use in the tests
        $content = "This is the contents of a text file";
        list($attachmentID, $attachment) = $this->createAttachment($content);

        $makeRequest = function($hookFunction, $justHeaders = true) use ($attachmentID) {
            $endpoint = "/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/getAttachmentWithHook/function/$hookFunction/id/$attachmentID";

            if($justHeaders) {
                return \RightNow\UnitTest\Helper::makeRequest($endpoint, array('justHeaders' => true));
            }
            else {
                return json_decode(\RightNow\UnitTest\Helper::makeRequest($endpoint), true);
            }
        };

        //Ensure that the pre_attachment_download hook is executed with the correct data
        $response = $makeRequest('preAttachmentDownloadHookEchoData', false);
        $response = $response['hookData'];
        $this->assertIdentical($response['name'], $attachment->FileName);
        $this->assertIdentical($response['mimetype'], $attachment->ContentType);
        $this->assertIdentical($response['size'], strlen($content));
        $this->assertTrue($response['preventBrowserDisplay']);

        //Ensure that calling get without hook returns the correct mimetype and content-disposition
        $response = $this->makeRequest("/ci/fattach/get/$attachmentID", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "Content-Disposition: attachment; filename=\"" . $attachment->FileName . "\""));
        $this->assertTrue(Text::stringContains($response, 'Content-Type: application/octet-stream'));

        //Now add the hook, the disposition and mimetype should be altered
        $response = $makeRequest('preAttachmentDownloadHookAlterData');
        $this->assertTrue(Text::stringContains($response, "Content-Disposition: inline; filename=\"" . $attachment->FileName . "\""));
        $this->assertTrue(Text::stringContains($response, 'Content-Type: text/plain;charset=US-ASCII'));
    }

    function getAttachmentWithHook() {
        $parameters = Text::getSubstringAfter($this->CI->uri->uri_string(), 'getAttachmentWithHook/');
        $parameters = explode('/', $parameters);

        $results = array();
        while(($count = count($parameters)) && $count % 2 === 0) {
            $results[array_shift($parameters)] = array_shift($parameters);
        }

        //Add a hook which will be invoked by the get method
        $this->setHook('pre_attachment_download', array(), $results['function'], false);

        //Now call the method which invokes the hook.
        $method = $this->getMethod('get');

        //Retrieve the given attachment and execute the hook
        $method($results['id']);
    }

    function preAttachmentDownloadHookAlterData(&$data) {
        $data['preventBrowserDisplay'] = false;
    }

    function preAttachmentDownloadHookEchoData($data) {
        exit(json_encode(array(
            'hookData' => $data
        )));
    }

    function testUploadHook() {
        //Write out the file being sent to the upload endpoint
        $content = "This is the contents of a text file";
        $filename = 'cp' . getmypid() . rand() . '.txt';
        $count = 1;
        $logFile = \RightNow\Api::cfg_path() . '/log/' . $filename;
        file_put_contents($logFile, $content);

        $makeRequest = function($hookFunction) use ($logFile) {
            $count = 1;
            $endpoint = "/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/uploadAttachmentWithHook/$hookFunction";
            $responseInfo = $this->sendFileWithConstraints($endpoint, $logFile, $count);
            return json_decode($responseInfo["body"], true);
        };

        //Ensure that the pre_attachment_download hook is executed with the correct data
        $response = $makeRequest('preAttachmentUploadHookEchoData');
        $response = $response['hookData'];
        $this->assertIdentical($response['name'], $filename);
        $this->assertIdentical($response['mimetype'], 'text/plain');
        $this->assertIdentical($response['size'], strlen($content));

        //No error message without hook
        $responseInfo = $this->sendFileWithConstraints('/ci/fattach/upload', $logFile, $count);
        $response = json_decode($responseInfo["body"], true);
        $this->assertIdentical($response['name'], $filename);
        $this->assertIdentical($response['type'], 'text/plain');
        $this->assertIdentical($response['size'], strlen($content));
        
        $responseInfo = $this->sendFileWithoutFtok('/ci/fattach/upload', $logFile, $count);
        $response = json_decode($responseInfo["body"], true);
        $this->assertIdentical($response['errorMessage'], \RightNow\Utils\Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG));

        //With hook, error prevents upload
        $response = $makeRequest('preAttachmentUploadHookError');
        $this->assertIdentical($response['errorMessage'], 'This is a test error message');

        unlink($logFile);
    }

    function uploadAttachmentWithHook() {
        $function = Text::getSubstringAfter($this->CI->uri->uri_string(), 'uploadAttachmentWithHook/');

        //Add a hook which will be invoked by the get method
        $this->setHook('pre_attachment_upload', array(), $function, false);

        //Now call the method which invokes the hook.
        $method = $this->getMethod('upload');
        $method();
    }

    function preAttachmentUploadHookEchoData($data) {
        exit(json_encode(array(
            'hookData' => $data
        )));
    }

    function preAttachmentUploadHookError($data) {
        return "This is a test error message";
    }

    function testUpload() {
        $response = $this->doUpload();
        $response = json_decode($response['body']);
        $this->verifyUpload($response);

        // replace matching < and >
        $filename = '<script>.txt';
        $response = $this->doUpload('upload', 'bananas', 1, $filename);
        $this->verifyUpload(json_decode($response['body']), str_replace(array('<', '>'), '-', $filename));

        // don't replace non-matching <
        $filename = '<script.txt';
        $response = $this->doUpload('upload', 'bananas', 1, $filename);
        $this->verifyUpload(json_decode($response['body']), str_replace(array('<'), '_', $filename));

        // don't replace non-matching >
        $filename = 'script>.txt';
        $response = $this->doUpload('upload', 'bananas', 1, $filename);
        $this->verifyUpload(json_decode($response['body']), str_replace(array('>'), '_', $filename));

        // replace matching &lt; and &gt;
        $filename = '&lt;script&gt;.txt';
        $response = $this->doUpload('upload', 'bananas', 1, $filename);
        $this->verifyUpload(json_decode($response['body']), str_replace(array('&lt;', '&gt;'), '-', $filename));

        // don't replace non-matching &lt;
        $filename = '&lt;script.txt';
        $response = $this->doUpload('upload', 'bananas', 1, $filename);
        $this->verifyUpload(json_decode($response['body']), $filename);

        // don't replace non-matching &gt;
        $filename = 'cpscript&gt;.txt';
        $response = $this->doUpload('upload', 'bananas', 1, $filename);
        $this->verifyUpload(json_decode($response['body']), $filename);

        // replace matching < and &gt;
        $filename = '<script&gt;.txt';
        $response = $this->doUpload('upload', 'bananas', 1, $filename);
        $this->verifyUpload(json_decode($response['body']), str_replace(array('<', '&gt;'), '-', $filename));

        // replace matching &lt; and >
        $filename = '&lt;script>.txt';
        $response = $this->doUpload('upload', 'bananas', 1, $filename);
        $this->verifyUpload(json_decode($response['body']), str_replace(array('&lt;', '>'), '-', $filename));

        // don't replace non-matching > and &lt;
        $filename = '>script&lt;.txt';
        $response = $this->doUpload('upload', 'bananas', 1, $filename);
        $this->verifyUpload(json_decode($response['body']), str_replace(array('>'), '_', $filename));

        // don't replace non-matching &gt; and <
        $filename = '&gt;script<.txt';
        $response = $this->doUpload('upload', 'bananas', 1, $filename);
        $this->verifyUpload(json_decode($response['body']), str_replace(array('<'), '_', $filename));

        // replace quote
        $filename = 'quote\'script.txt';
        $response = $this->doUpload('upload', 'bananas', 1, $filename);
        $this->verifyUpload(json_decode($response['body']), str_replace("'", '-', $filename));

        // encode double quote
        $filename = 'quote"script.txt';
        $response = $this->doUpload('upload', 'bananas', 1, $filename);
        $this->verifyUpload(json_decode($response['body']), str_replace('"', '%22', $filename));
    }

    //@@@ QA 130524-000079
    function testSanitizeFilename() {
        $method = $this->getMethod('_sanitizeFilename');
        $this->assertSame('cp-script-', $method('cp<script>'));
        $this->assertSame('cp_script', $method('cp<script'));
        $this->assertSame('cpscript_', $method('cpscript>'));
        $this->assertSame('cp-script-', $method('cp&lt;script&gt;'));
        $this->assertSame('cp&lt;script', $method('cp&lt;script'));
        $this->assertSame('cpscript&gt;', $method('cpscript&gt;'));
        $this->assertSame('cp-script-', $method('cp<script&gt;'));
        $this->assertSame('cp-script-', $method('cp&lt;script>'));
        $this->assertSame('cp_script&lt;', $method('cp>script&lt;'));
        $this->assertSame('cp&gt;script_', $method('cp&gt;script<'));
        $this->assertSame('cp--script', $method('cp"\'script'));
    }

    function testTempUploadAccessFlow() {
        $sessionCookie = $this->CI->input->cookie('cp_session');

        $response = $this->doUpload('uploadInlineContent', 'bananas', 1);
        $fileInfo = json_decode($response['body']);

        $this->verifyUpload($fileInfo);
        $this->assertSame($fileInfo->size, strlen('bananas'));

        // Scrape the cookie out of the upload's response so that we can persist the session.
        preg_match("/^Set-Cookie: (.*)$/m", $response['headers'], $matches);
        $sessionCookie = trim($matches[1]);

        // Same session and correct file name can access the file
        $response = $this->makeRequest("/ci/fattach/getTemp/{$fileInfo->tmp_name}", array('cookie' => $sessionCookie, 'noDevCookie' => true));
        $this->assertSame('bananas', $response);
        $response = $this->makeRequest("/ci/fattach/getTemp/{$fileInfo->tmp_name}", array('cookie' => $sessionCookie, 'justHeaders' => true, 'noDevCookie' => true));
        $this->assertTrue(Text::stringContains($response, "filename={$fileInfo->name}"));

        // No file name
        $response = $this->makeRequest("/ci/fattach/getTemp/", array('justHeaders' => true, 'cookie' => $sessionCookie, 'noDevCookie' => true));
        $this->verifyErrorRedirect($response, 4);

        // File name doesn't exist
        $response = $this->makeRequest("/ci/fattach/getTemp/bananas", array('justHeaders' => true, 'cookie' => $sessionCookie, 'noDevCookie' => true));
        $this->verifyErrorRedirect($response, 4);

        // Correct file name, but session ids don't match
        $response = $this->makeRequest("/ci/fattach/getTemp/{$fileInfo->tmp_name}", array('justHeaders' => true, 'noDevCookie' => true));
        $this->verifyErrorRedirect($response, 4);
    }

    function testRenderJSON() {
        // Make a file in the log dir, since we can write there
        $filename = 'cp' . getmypid() . '.txt';
        $count = 1;
        $logFile = \RightNow\Api::cfg_path() . '/log/' . $filename;
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
        $verifyUpload($this->sendFileWithConstraints("/ci/Fattach/upload", $logFile, $count), false, $this);

        // verify errors are also plain text in response
        $endpoint = "/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/uploadAttachmentWithHook/preAttachmentUploadHookError";
        $verifyUpload($this->sendFileWithConstraints($endpoint, $logFile, $count), true, $this);

        @unlink($logFile);
    }

    function testContentDisposition() {
        $method = $this->getMethod('_getContentDisposition');
        $this->assertSame('inline', $method('', ''));
        $this->assertSame('inline', $method('whatever', 1232));
        $this->assertSame('inline', $method('banana.pdf', (20 * 1024 * 1024)));
        $this->assertSame('attachment', $method('', '', true));
        $this->assertSame('attachment', $method('banana.docx', 1232));
        $this->assertSame('attachment', $method('bana-Îna.doc', 1232));
        $this->assertSame('attachment', $method('banana.xls', 1232));
        $this->assertSame('attachment', $method('banana.xlsx', 1232));
        $this->assertSame('attachment', $method('whatever', (20 * 1024 * 1024) + 1));
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; U; Android 2.2; en-us; Nexus One Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1';
        $this->assertSame('attachment', $method('banana.pdf', 1232));
    }
    
    function testCheckForValidFormToken() {
        $method = $this->getMethod('checkForValidFormToken');
        $originalPost = $_POST;
        
        //f_tok not present
        $this->assertFalse($method("standard/input/FileAttachmentUpload"));
        
        //f_tok present
        $f_tok = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $_POST = array_merge($_POST, array('f_tok' => $f_tok));
        $this->assertTrue($method("standard/input/FileAttachmentUpload"));
        $this->assertFalse($method("standard/input/FileAttachmentUpload"));
        
        $this->assertTrue($method("custom/input/FileAttachmentUpload"));
        
        $_POST = $originalPost;        
    }
}
