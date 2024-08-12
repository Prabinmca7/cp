<?php

require_once CORE_FILES . 'compatibility/Internal/SiebelApi.php';

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Connect\v1_4 as Connect,
    RightNow\Internal\Api,
    RightNow\Internal\Libraries\SiebelRequest,
    RightNow\UnitTest\Helper,
    RightNow\Utils\Config,
    RightNow\Utils\Text;

class SiebelRequestTest extends CPTestCase
{
    public $testingClass = 'RightNow\Internal\Libraries\SiebelRequest';
    protected $hookEndpointClass = __CLASS__;
    protected $hookEndpointFilePath = __FILE__;

    public function testConstructor(){
        $previousValues = Helper::getConfigValues(array('SIEBEL_EAI_HOST', 'SIEBEL_EAI_LANGUAGE', 'SIEBEL_EAI_USERNAME', 'SIEBEL_EAI_PASSWORD'));

        Helper::setConfigValues(array('SIEBEL_EAI_HOST' => 'alice', 'SIEBEL_EAI_LANGUAGE' => 'bob', 'SIEBEL_EAI_USERNAME' => 'Carol', 'SIEBEL_EAI_PASSWORD' => 'Dave'));

        $requestHeaderApi = <<<HEADER
<soapenv:Envelope xmlns:soapenv='http://schemas.xmlsoap.org/soap/envelope/' xmlns:ser='http://siebel.com/Service/FS/ServiceRequests' xmlns:data='http://www.siebel.com/xml/Service%20Request/Data' xmlns:ws='http://siebel.com/webservices'>
    <soapenv:Header>
        <ws:UsernameToken>Carol</ws:UsernameToken>
        <ws:PasswordText>Dave</ws:PasswordText>
    </soapenv:Header>
    <soapenv:Body>
        <ser:ServiceRequestInsert_Input>
            <data:ListOfWc_Service_Request_Io lastpage='' recordcount=''>
                <data:ServiceRequest>
HEADER;
        $requestFooterApi = <<<FOOTER
                </data:ServiceRequest>
            </data:ListOfWc_Service_Request_Io>
            <ser:LOVLanguageMode>LDC</ser:LOVLanguageMode>
            <ser:ViewMode>All</ser:ViewMode>
        </ser:ServiceRequestInsert_Input>
    </soapenv:Body>
</soapenv:Envelope>
FOOTER;

        $incidentObject = new Connect\Incident();
        $incidentObject->Subject = 'bobby!';
        $siebelRequest = new SiebelRequest(
            array('dataIs' => 'asDataDoes'),
            array('form' => 'isData'),
            $incidentObject
        );
        list($class, $siebelUrl, $soapAction, $requestHeader, $requestFooter, $siebelData, $formData, $incident) = $this->reflect('siebelUrl', 'soapAction', 'requestHeader', 'requestFooter', 'siebelData', 'formData', 'incident');
        $this->assertIdentical($siebelUrl->getValue($siebelRequest), 'https://alice/eai_bob/start.swe?SWEExtSource=SecureWebService&SWEExtCmd=Execute&WSSOAP=1');
        $this->assertIdentical($soapAction->getValue($siebelRequest), 'document/http://siebel.com/Service/FS/ServiceRequests:ServiceRequestInsert');
        $this->assertIdentical($requestHeader->getValue($siebelRequest), $requestHeaderApi);
        $this->assertIdentical($requestFooter->getValue($siebelRequest), $requestFooterApi);
        $this->assertIdentical($siebelData->getValue($siebelRequest), array('dataIs' => 'asDataDoes'));
        $this->assertIdentical($formData->getValue($siebelRequest), array('form' => 'isData'));
        $this->assertIdentical($incident->getValue($siebelRequest), $incidentObject);
        $this->assertIdentical($incident->getValue($siebelRequest)->Subject, 'bobby!');

        Helper::setConfigValues($previousValues);
    }

    public function testMakeRequest(){
        $siebelRequest = new SiebelRequest(array('Abstract' => 'abstract', 'Description' => 'description'), array(), new Connect\Incident());

        list($class, $siebelUrl) = $this->reflect('siebelUrl');
        $url = 'http://' . Config::getConfig(OE_WEB_SERVER) . '/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/siebelEndpoint';
        $siebelUrl->setValue($siebelRequest, "$url/0");
        $siebelRequest->makeRequest();
        $this->assertNull($siebelRequest->getErrors());

        $siebelUrl->setValue($siebelRequest, "$url/1");
        $this->setHook('post_siebel_incident_error', array(), 'hookEndpoint', false);
        $siebelRequest->makeRequest();
        $this->assertIdentical($siebelRequest->getErrors(), array(Config::getMessage(SORRY_PROCESSING_SUBMISSION_PLS_MSG)));
        $this->assertIdentical(self::$hookData['responseInfo']['http_code'], 500);

        $siebelUrl->setValue($siebelRequest, "$url/2");
        $this->setHook('post_siebel_incident_error', array(), 'hookEndpoint', false);
        $siebelRequest->makeRequest();
        $this->assertIdentical($siebelRequest->getErrors(), array(Config::getMessage(SORRY_PROCESSING_SUBMISSION_PLS_MSG)));
        $this->assertIdentical(self::$hookData['responseInfo']['http_code'], 500);
        $this->assertIdentical(self::$hookData['requestBody'], "<data:Abstract>abstract</data:Abstract><data:Description>description</data:Description>");

        $this->setHook('post_siebel_incident_error', array(), 'callThisHookReturnString', false);
        $siebelRequest->makeRequest();
        $this->assertIdentical($siebelRequest->getErrors(), array('what are you doing?'));
        $this->assertIdentical(self::$hookData['responseInfo']['http_code'], 500);
        $this->assertIdentical(self::$hookData['requestBody'], "<data:Abstract>abstract</data:Abstract><data:Description>description</data:Description>");

        $this->setHooks(array(
            array('name' => 'pre_siebel_incident_submit', 'function' => 'tweakRequestData'),
        ));
        $siebelRequest->makeRequest();
        $this->assertIdentical($siebelRequest->getErrors(), array('what are you doing?'));
        $this->assertIdentical(self::$hookData['responseInfo']['http_code'], 500);
        $this->assertIdentical(self::$hookData['requestBody'], "<data:Abstract>thisAbstract</data:Abstract><data:Description>thisDescription</data:Description>");
        $siebelModelHookData = array('shouldSave' => true, 'formData' => array(), 'incident' => new Connect\Incident());
        // model function returns a string when there's an error
        $this->assertIdentical($this->CI->model('Siebel')->processRequest($siebelModelHookData), 'what are you doing?');

        $this->setHook('post_siebel_incident_error', array(), 'unsetErrors', false);
        $siebelRequest->makeRequest();
        $this->assertNull($siebelRequest->getErrors());
        $siebelModelHookData = array('shouldSave' => true, 'formData' => array(), 'incident' => new Connect\Incident());
        // model function returns null when there's an error and the hook removes that error
        $this->assertNull($this->CI->model('Siebel')->processRequest($siebelModelHookData));
    }

    public function tweakRequestData(&$hookData){
        $hookData['siebelData']['Abstract'] = 'thisAbstract';
        $hookData['siebelData']['Description'] = 'thisDescription';
    }

    public function unsetErrors(&$hookData){
        unset($hookData['errors']);
    }

    public function testGetErrors(){
        $siebelRequest = new SiebelRequest(array(), array(), new Connect\Incident());

        list($class, $errors, $getErrors) = $this->reflect('errors', 'method:getErrors');

        $errors->setValue($siebelRequest, array());
        $this->assertIdentical($getErrors->invoke($siebelRequest), array());

        $errors->setValue($siebelRequest, array('bobloblaw'));
        $this->assertIdentical($getErrors->invoke($siebelRequest), array('bobloblaw'));
    }

    public function testResetData(){
        $siebelRequest = new SiebelRequest(array(), array(), new Connect\Incident());

        list($class, $siebelUrl, $soapAction, $resetData) = $this->reflect('siebelUrl', 'soapAction', 'method:resetData');

        $previousSiebelUrl = $siebelUrl->getValue($siebelRequest);

        $resetData->invoke($siebelRequest, array('soapAction' => 'fancy'));

        $this->assertIdentical($siebelUrl->getValue($siebelRequest), $previousSiebelUrl);
        $this->assertIdentical($soapAction->getValue($siebelRequest), 'fancy');
    }

    public function testMakeSiebelRequest(){
        $siebelRequest = new SiebelRequest(array('Abstract' => 'abstract', 'Description' => 'description'), array(), new Connect\Incident());

        list($class, $makeSiebelRequest, $siebelUrl) = $this->reflect('method:makeSiebelRequest', 'siebelUrl');
        $url = 'http://' . Config::getConfig(OE_WEB_SERVER) . '/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/siebelEndpoint';
        $siebelUrl->setValue($siebelRequest, "$url/0");
        $makeSiebelRequest->invoke($siebelRequest);
        $this->assertNull($siebelRequest->getErrors());

        $siebelUrl->setValue($siebelRequest, "$url/1");
        $this->setHook('post_siebel_incident_error', array(), 'hookEndpoint', false);
        $makeSiebelRequest->invoke($siebelRequest);
        $this->assertIdentical($siebelRequest->getErrors(), array(Config::getMessage(SORRY_PROCESSING_SUBMISSION_PLS_MSG)));
        $this->assertIdentical(self::$hookData['responseInfo']['http_code'], 500);

        $siebelUrl->setValue($siebelRequest, "$url/2");
        $this->setHook('post_siebel_incident_error', array(), 'hookEndpoint', false);
        $makeSiebelRequest->invoke($siebelRequest);
        $this->assertIdentical($siebelRequest->getErrors(), array(Config::getMessage(SORRY_PROCESSING_SUBMISSION_PLS_MSG)));
        $this->assertIdentical(self::$hookData['responseInfo']['http_code'], 500);
        $this->assertIdentical(self::$hookData['requestBody'], "<data:Abstract>abstract</data:Abstract><data:Description>description</data:Description>");

        $this->setHook('post_siebel_incident_error', array(), 'callThisHookReturnString', false);
        $makeSiebelRequest->invoke($siebelRequest);
        $this->assertIdentical($siebelRequest->getErrors(), array('what are you doing?'));
        $this->assertIdentical(self::$hookData['responseInfo']['http_code'], 500);
        $this->assertIdentical(self::$hookData['requestBody'], "<data:Abstract>abstract</data:Abstract><data:Description>description</data:Description>");
    }

    public function siebelEndpoint(){
        $type = Text::getSubstringAfter($this->CI->uri->uri_string(), 'siebelEndpoint/');
        if ($type === '0') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK', true, 200);
            echo "<ServiceRequest><Id>PASS</Id>";
        }
        else if ($type === '1') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            echo "<ServiceRequest><Id>PASS</Id>";
        }
        else if ($type === '2') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            echo file_get_contents('php://input');
        }
    }

    public function callThisHookReturnString($hookData){
        self::$hookData = $hookData;
        return 'what are you doing?';
    }

    public function testOutputSiebelErrors(){
        // setup logging
        $logPath = \RightNow\Api::cfg_path() . '/log';
        umask(0);
        file_put_contents("$logPath/tr.cphp", 'ALL');
        file_put_contents("$logPath/tr.acs", 'ALL');

        $now = time();
        $url = "/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/callOutputSiebelErrors/$now";
        $getClickstream = function($now, $type) {
            $sql = "SELECT cs.c_id, cs.cs_session_id, cs.app, csa.action, cs.context1, cs.context2, cs.context3"
                . " FROM clickstreams cs JOIN cs_actions csa ON cs.action_id = csa.action_id"
                . " WHERE cs.c_id = $type AND cs.context1 LIKE '%$now.$type%'";

            $si = Api::sql_prepare($sql);
            $i = 0;
            Api::sql_bind_col($si, ++$i, BIND_INT, 0);   // c_id
            Api::sql_bind_col($si, ++$i, BIND_NTS, 12);  // cs_session_id
            Api::sql_bind_col($si, ++$i, BIND_INT, 0);   // cs.app
            Api::sql_bind_col($si, ++$i, BIND_NTS, 256); // csa.action
            Api::sql_bind_col($si, ++$i, BIND_NTS, 256); // cs.context1
            Api::sql_bind_col($si, ++$i, BIND_NTS, 256); // cs.context2
            Api::sql_bind_col($si, ++$i, BIND_NTS, 256); // cs.context3

            $row = Api::sql_fetch($si);
            Api::sql_free($si);

            return $row;
        };
        $verifyNormalParts = function($that, $clickstream, $now, $type) {
            $that->assertIdentical($clickstream[0], intval($type));
            $that->assertIdentical($clickstream[1], $type);
            $that->assertIdentical($clickstream[2], 1);
            $that->assertIdentical($clickstream[3], 'siebel_integration_error');
            $that->assertIdentical($clickstream[5], '');
            $that->assertIdentical($clickstream[6], '');
        };

        $testTypes = array(
            array(
                'type' => '10',
                'click' => "Now: $now.10, RequestErrorNumber: 123, RequestErrorMessage: ERROR!, HTTP_CODE: 55, Response: RESPONSE, RequestBody: REQUEST",
                'trout' => "Now: $now.10, RequestErrorNumber: 123, RequestErrorMessage: ERROR!, HTTP_CODE: 55, Response: RESPONSE, RequestBody: REQUEST",
                'acs1' => '"level":"error","subject":"siebel","verb":"error",',
                'acs2' => ',"$Now":"' . "$now.10" . '","#RequestErrorNumber":123,"$RequestErrorMessage":"ERROR!","#HTTP_CODE":55,"$Response":"RESPONSE","$RequestBody":"REQUEST"',
            ),
            array(
                'type' => '11',
                'click' => "Now: $now.11, RequestErrorNumber: 123, RequestErrorMessage: ERROR!, HTTP_CODE: 55, Response: RESPONSE, RequestBody: REQUEST" . str_pad('.', 125, '.'),
                'trout' => "Now: $now.11, RequestErrorNumber: 123, RequestErrorMessage: ERROR!, HTTP_CODE: 55, Response: RESPONSE, RequestBody: REQUEST" . str_pad('.', 200, '.'),
                'acs1' => '"level":"error","subject":"siebel","verb":"error",',
                'acs2' => ',"$Now":"' . "$now.11" . '","#RequestErrorNumber":123,"$RequestErrorMessage":"ERROR!","#HTTP_CODE":55,"$Response":"RESPONSE","$RequestBody":"REQUEST' . str_pad('.', 200, '.') . '"',
            ),
            array(
                'type' => '12',
                'click' => "Now: $now.12, RequestErrorNumber: 123, RequestErrorMessage: ERROR!, HTTP_CODE: 55, Response: RESPONSE" . str_pad('.', 147, '.'),
                'trout' => "Now: $now.12, RequestErrorNumber: 123, RequestErrorMessage: ERROR!, HTTP_CODE: 55, Response: RESPONSE" . str_pad('.', 200, '.') . ", RequestBody: REQUEST",
                'acs1' => '"level":"error","subject":"siebel","verb":"error",',
                'acs2' => ',"$Now":"' . "$now.12" . '","#RequestErrorNumber":123,"$RequestErrorMessage":"ERROR!","#HTTP_CODE":55,"$Response":"RESPONSE' . str_pad('.', 200, '.') . '","$RequestBody":"REQUEST"',
            ),
            array(
                'type' => '13',
                'click' => "Now: $now.13, RequestErrorNumber: 123, RequestErrorMessage: ERROR!, HTTP_CODE: 55, Response: RESPONSE" . str_pad('.', 147, '.'),
                'trout' => "Now: $now.13, RequestErrorNumber: 123, RequestErrorMessage: ERROR!, HTTP_CODE: 55, Response: RESPONSE" . str_pad('.', 200, '.') . ", RequestBody: REQUEST" . str_pad('.', 200, '.'),
                'acs1' => '"level":"error","subject":"siebel","verb":"error",',
                'acs2' => ',"$Now":"' . "$now.13" . '","#RequestErrorNumber":123,"$RequestErrorMessage":"ERROR!","#HTTP_CODE":55,"$Response":"RESPONSE' . str_pad('.', 200, '.') . '","$RequestBody":"REQUEST' . str_pad('.', 200, '.') . '"',
            ),
        );

        foreach ($testTypes as $testValues) {
            $this->assertIdentical('', Helper::makeRequest("$url/{$testValues['type']}"));
        }

        foreach ($testTypes as $testValues) {
            $type = $testValues['type'];
            $clickstream = $getClickstream($now, $type);
            $verifyNormalParts($this, $clickstream, $now, $type);
            $expectedFirstMessage = "Now: $now.10, RequestErrorNumber: 123, RequestErrorMessage: ERROR!, HTTP_CODE: 55, Response: RESPONSE, RequestBody: REQUEST";
            $expectedFirstMessageAcsFirstPart = '"level":"error","subject":"siebel","verb":"error",';
            $expectedFirstMessageAcsSecondPart = ',"$Now":"' . "$now.10" . '","#RequestErrorNumber":123,"$RequestErrorMessage":"ERROR!","#HTTP_CODE":55,"$Response":"RESPONSE","$RequestBody":"REQUEST"';

            $expectedTroutMessage = $testValues['trout'];
            $expectedAcsMessage1 = $testValues['acs1'];
            $expectedAcsMessage2 = $testValues['acs2'];

            $this->assertIdentical($clickstream[4], $testValues['click']);

            $logged = false;
            foreach(glob("$logPath/cphp*.tr") as $logFile) {
                $logFileContents = file_get_contents($logFile);
                if (Text::stringContains($logFileContents, $expectedTroutMessage)) {
                    $logged = true;
                    break;
                }
            }
            $this->assertTrue($logged, "Did not find expected message ($expectedTroutMessage) in phpoutlog");

            $logged = false;
            foreach(glob("$logPath/acs/*.log") as $logFile) {
                $logFileContents = fopen($logFile, 'r');
                while (($line = fgets($logFileContents)) !== false) {
                    if (Text::stringContains($line, $expectedAcsMessage1) && Text::stringContains($line, $expectedAcsMessage2)) {
                        $logged = $line;
                        break;
                    }
                }
                fclose($logFileContents);
            }
            $this->assertIsA($logged, 'string', "Did not find expected message ($expectedAcsMessage1, $expectedAcsMessage2) in ACS");
        }

        foreach(glob("$logPath/cphp*.tr") as $logFile) {
            unlink($logFile);
        }
        foreach(glob("$logPath/acs/*.log") as $logFile) {
            unlink($logFile);
        }
        unlink("$logPath/tr.cphp");
        unlink("$logPath/tr.acs");
    }

    public function callOutputSiebelErrors() {
        $parameters = Text::getSubstringAfter($this->CI->uri->uri_string(), 'callOutputSiebelErrors/');
        list($now, $type) = explode('/', $parameters);
        $siebelRequest = new SiebelRequest(array(), array(), new Connect\Incident());

        $errors = array(
            'Now' => $now . '.' . $type,
            'RequestErrorNumber' => 123,
            'RequestErrorMessage' => "ERROR!",
            'HTTP_CODE' => 55,
            'Response' => "RESPONSE",
            'RequestBody' => "REQUEST",
        );

        list($class, $outputSiebelErrors) = $this->reflect('method:outputSiebelErrors');
        if ($type === '10') {
            $outputSiebelErrors->invoke($siebelRequest, $errors, $type, intval($type));
        }
        else if ($type === '11') {
            $errors['RequestBody'] .= str_pad('.', 200, '.');
            $outputSiebelErrors->invoke($siebelRequest, $errors, $type, intval($type));
        }
        else if ($type === '12') {
            $errors['Response'] .= str_pad('.', 200, '.');
            $outputSiebelErrors->invoke($siebelRequest, $errors, $type, intval($type));
        }
        else if ($type === '13') {
            $errors['RequestBody'] .= str_pad('.', 200, '.');
            $errors['Response'] .= str_pad('.', 200, '.');
            $outputSiebelErrors->invoke($siebelRequest, $errors, $type, intval($type));
        }
    }

    public function testCreateErrorMessage(){
        $siebelRequest = new SiebelRequest(array(), array(), new Connect\Incident());

        list($class, $createErrorMessage) = $this->reflect('method:createErrorMessage');

        $this->assertIdentical($createErrorMessage->invoke($siebelRequest, array()), '');
        $this->assertIdentical($createErrorMessage->invoke($siebelRequest, array('error1' => 'Danger, Will Robinson!')), 'error1: Danger, Will Robinson!');
        $this->assertIdentical($createErrorMessage->invoke($siebelRequest, array('error1' => 'Danger, Will Robinson!', 'error2' => 'Danger! Danger! Danger!')), 'error1: Danger, Will Robinson!, error2: Danger! Danger! Danger!');
    }
}
