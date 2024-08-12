<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Internal\Api,
    RightNow\Internal\SiebelApi,
    RightNow\UnitTest\Helper;

class InternalSiebelApiTest extends CPTestCase
{
    public $testingClass = 'RightNow\Internal\SiebelApi';
    private static $siebelHost = array('slc05ptd.us.oracle.com', 'slc04wlr.us.oracle.com');

    public function testGenerateRequestParts(){
        $method = $this->getMethod('generateRequestParts');

        $previousValues = Helper::getConfigValues(array('SIEBEL_EAI_HOST', 'SIEBEL_EAI_LANGUAGE', 'SIEBEL_EAI_USERNAME', 'SIEBEL_EAI_PASSWORD'));

        Helper::setConfigValues(array('SIEBEL_EAI_HOST' => 'alice', 'SIEBEL_EAI_LANGUAGE' => 'bob', 'SIEBEL_EAI_USERNAME' => 'Carol', 'SIEBEL_EAI_PASSWORD' => 'Dave'));

        $requestHeader = <<<HEADER
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
        $requestFooter = <<<FOOTER
                </data:ServiceRequest>
            </data:ListOfWc_Service_Request_Io>
            <ser:LOVLanguageMode>LDC</ser:LOVLanguageMode>
            <ser:ViewMode>All</ser:ViewMode>
        </ser:ServiceRequestInsert_Input>
    </soapenv:Body>
</soapenv:Envelope>
FOOTER;

        $this->assertIdentical(
            array(
                'siebelUrl' => 'https://alice/eai_bob/start.swe?SWEExtSource=SecureWebService&SWEExtCmd=Execute&WSSOAP=1',
                'soapAction' => 'document/http://siebel.com/Service/FS/ServiceRequests:ServiceRequestInsert',
                'requestHeader' => $requestHeader,
                'requestFooter' => $requestFooter,
            ),
            $method()
        );

        Helper::setConfigValues($previousValues);
    }

    public function testMakeRequest(){
        $method = $this->getMethod('makeRequest');

        $previousValues = Helper::getConfigValues(array('SIEBEL_EAI_VALIDATE_CERTIFICATE', 'USE_KNOWN_ROOT_CAS'));
        Helper::setConfigValues(array('SIEBEL_EAI_VALIDATE_CERTIFICATE' => false, 'USE_KNOWN_ROOT_CAS' => false));

        $requestHeader = <<<HEADER
<soapenv:Envelope xmlns:soapenv='http://schemas.xmlsoap.org/soap/envelope/' xmlns:ser='http://siebel.com/Service/FS/ServiceRequests' xmlns:data='http://www.siebel.com/xml/Service%20Request/Data' xmlns:ws='http://siebel.com/webservices'>
    <soapenv:Header>
        <ws:UsernameToken>SADMIN</ws:UsernameToken>
        <ws:PasswordText>MSSQL</ws:PasswordText>
    </soapenv:Header>
    <soapenv:Body>
        <ser:ServiceRequestInsert_Input>
            <data:ListOfWc_Service_Request_Io lastpage='' recordcount=''>
                <data:ServiceRequest>
HEADER;
        $requestFooter = <<<FOOTER
                </data:ServiceRequest>
            </data:ListOfWc_Service_Request_Io>
            <ser:LOVLanguageMode>LDC</ser:LOVLanguageMode>
            <ser:ViewMode>All</ser:ViewMode>
        </ser:ServiceRequestInsert_Input>
    </soapenv:Body>
</soapenv:Envelope>
FOOTER;

        if (Api::intf_name() === 'jvswtrunk') {
            foreach (self::$siebelHost as $siebelHost) {
                // sweet, buttery goodness
                $results = $method(
                    array('Abstract' => 'test', 'Description' => 'test'),
                    'https://' . $siebelHost . '/eai_enu/start.swe?SWEExtSource=SecureWebService&SWEExtCmd=Execute&WSSOAP=1',
                    'document/http://siebel.com/Service/FS/ServiceRequests:ServiceRequestInsert',
                    $requestHeader,
                    $requestFooter
                );
                $this->assertTrue($results['success'], "failed with server $siebelHost " . var_export($results['success'], true));
                if ($results['success']) {
                    $this->assertIdentical(1, preg_match('#<ServiceRequest><Id>[A-Z0-9-]+</Id>#', $results['response']));
                    $this->assertIdentical('<data:Abstract>test</data:Abstract><data:Description>test</data:Description>', $results['requestBody']);
                    $this->assertIdentical(0, $results['requestErrorNumber']);
                    $this->assertIdentical('', $results['requestErrorMessage']);
                    $this->assertIdentical(200, $results['responseInfo']['http_code']);
                }

                // sweet, buttery goodness with a slathering of description data
                $results = $method(
                    array('Abstract' => 'test', 'Description' => 'test' . str_pad('', 247, ".")),
                    'https://' . $siebelHost . '/eai_enu/start.swe?SWEExtSource=SecureWebService&SWEExtCmd=Execute&WSSOAP=1',
                    'document/http://siebel.com/Service/FS/ServiceRequests:ServiceRequestInsert',
                    $requestHeader,
                    $requestFooter
                );
                $this->assertTrue($results['success'], "failed with server $siebelHost (are Inbox workflows enabled?)");
                if ($results['success']) {
                    $this->assertIdentical(1, preg_match('#<ServiceRequest><Id>[A-Z0-9-]+</Id>#', $results['response']));
                    $this->assertIdentical('<data:Abstract>test</data:Abstract><data:Description>test' . str_pad('', 247, ".") . '</data:Description>', $results['requestBody']);
                    $this->assertIdentical(0, $results['requestErrorNumber']);
                    $this->assertIdentical('', $results['requestErrorMessage']);
                    $this->assertIdentical(200, $results['responseInfo']['http_code']);
                }

                // sweet, buttery goodness with a slathering of description data
                $results = $method(
                    array('Abstract' => 'test', 'Description' => 'test' . str_pad('', 1996, ".")),
                    'https://' . $siebelHost . '/eai_enu/start.swe?SWEExtSource=SecureWebService&SWEExtCmd=Execute&WSSOAP=1',
                    'document/http://siebel.com/Service/FS/ServiceRequests:ServiceRequestInsert',
                    $requestHeader,
                    $requestFooter
                );
                $this->assertTrue($results['success'], "failed with server $siebelHost (are Inbox workflows enabled?)");
                if ($results['success']) {
                    $this->assertIdentical(1, preg_match('#<ServiceRequest><Id>[A-Z0-9-]+</Id>#', $results['response']));
                    $this->assertIdentical('<data:Abstract>test</data:Abstract><data:Description>test' . str_pad('', 1996, ".") . '</data:Description>', $results['requestBody']);
                    $this->assertIdentical(0, $results['requestErrorNumber']);
                    $this->assertIdentical('', $results['requestErrorMessage']);
                    $this->assertIdentical(200, $results['responseInfo']['http_code']);
                }

                // too much description data
                $results = $method(
                    array('Abstract' => 'test', 'Description' => 'test' . str_pad('', 1997, ".")),
                    'https://' . $siebelHost . '/eai_enu/start.swe?SWEExtSource=SecureWebService&SWEExtCmd=Execute&WSSOAP=1',
                    'document/http://siebel.com/Service/FS/ServiceRequests:ServiceRequestInsert',
                    $requestHeader,
                    $requestFooter
                );
                $this->assertFalse($results['success']);
                $this->assertIdentical(0, preg_match('#<ServiceRequest><Id>[A-Z0-9-]+</Id>#', $results['response']));
                $this->assertTrue(\RightNow\Utils\Text::stringContains($results['response'], "which is longer than allowed length of 2000 characters.(SBL-EAI-13011)"));
                $this->assertIdentical('<data:Abstract>test</data:Abstract><data:Description>test' . str_pad('', 1997, ".") . '</data:Description>', $results['requestBody']);
                $this->assertIdentical(0, $results['requestErrorNumber']);
                $this->assertIdentical('', $results['requestErrorMessage']);
                $this->assertIdentical(500, $results['responseInfo']['http_code']);

                // garbage data
                $results = $method(
                    array('Abstract123' => 'test', 'Description' => 'test'),
                    'https://' . $siebelHost . '/eai_enu/start.swe?SWEExtSource=SecureWebService&SWEExtCmd=Execute&WSSOAP=1',
                    'document/http://siebel.com/Service/FS/ServiceRequests:ServiceRequestInsert',
                    $requestHeader,
                    $requestFooter
                );

                $this->assertFalse($results['success']);
                $this->assertIdentical(0, preg_match('#<ServiceRequest><Id>[A-Z0-9-]+</Id>#', $results['response']));
                $this->assertTrue(\RightNow\Utils\Text::stringContains($results['response'], "Element with XML tag &apos;Abstract123&apos; is not found in the definition of EAI Integration Component &apos;Service Request&apos;(SBL-EAI-04127)"));
                $this->assertIdentical('<data:Abstract123>test</data:Abstract123><data:Description>test</data:Description>', $results['requestBody']);
                $this->assertIdentical(0, $results['requestErrorNumber']);
                $this->assertIdentical('', $results['requestErrorMessage']);
                $this->assertIdentical(500, $results['responseInfo']['http_code']);

                // garbage header/footer
                $results = $method(
                    array('Abstract' => 'test', 'Description' => 'test'),
                    'https://' . $siebelHost . '/eai_enu/start.swe?SWEExtSource=SecureWebService&SWEExtCmd=Execute&WSSOAP=1',
                    'document/http://siebel.com/Service/FS/ServiceRequests:ServiceRequestInsert',
                    'alice',
                    'bob'
                );
                $this->assertFalse($results['success']);
                $this->assertIdentical(0, preg_match('#<ServiceRequest><Id>[A-Z0-9-]+</Id>#', $results['response']));
                $this->assertTrue(\RightNow\Utils\Text::stringContains($results['response'], "Error Code: 10944643 Error Message: Error: nbound SOAP Message - XML parsing failed, Fatal Error at line : 1 char : 1, Message : Invalid document structure"));
                $this->assertIdentical('<data:Abstract>test</data:Abstract><data:Description>test</data:Description>', $results['requestBody']);
                $this->assertIdentical(0, $results['requestErrorNumber']);
                $this->assertIdentical('', $results['requestErrorMessage']);
                $this->assertIdentical(500, $results['responseInfo']['http_code']);

                // garbage soap action
                $results = $method(
                    array('Abstract' => 'test', 'Description' => 'test'),
                    'https://' . $siebelHost . '/eai_enu/start.swe?SWEExtSource=SecureWebService&SWEExtCmd=Execute&WSSOAP=1',
                    'document/http://siebel.com/Service/FS/ServiceRequests:ServiceRequestInsertMonkeys',
                    $requestHeader,
                    $requestFooter
                );
                $this->assertFalse($results['success']);
                $this->assertIdentical(0, preg_match('#<ServiceRequest><Id>[A-Z0-9-]+</Id>#', $results['response']));
                $this->assertTrue(\RightNow\Utils\Text::stringContains($results['response'], "There is no active Web Service with operation named &apos;http://siebel.com/Service/FS/ServiceRequests:ServiceRequestInsertMonkeys&apos;.(SBL-EAI-04313)"));
                $this->assertIdentical('<data:Abstract>test</data:Abstract><data:Description>test</data:Description>', $results['requestBody']);
                $this->assertIdentical(0, $results['requestErrorNumber']);
                $this->assertIdentical('', $results['requestErrorMessage']);
                $this->assertIdentical(500, $results['responseInfo']['http_code']);
            }
        }

        // garbage URL
        $results = $method(
            array('Abstract' => 'test', 'Description' => 'test'),
            'https://notevenclose.us.oracle.com/eai_enu/start.swe?SWEExtSource=SecureWebService&SWEExtCmd=Execute&WSSOAP=1',
            'document/http://siebel.com/Service/FS/ServiceRequests:ServiceRequestInsert',
            $requestHeader,
            $requestFooter
        );
        $this->assertFalse($results['success']);
        $this->assertIdentical(0, preg_match('#<ServiceRequest><Id>[A-Z0-9-]+</Id>#', $results['response']));
        $this->assertFalse($results['response']);
        $this->assertIdentical('<data:Abstract>test</data:Abstract><data:Description>test</data:Description>', $results['requestBody']);
        $this->assertIdentical(6, $results['requestErrorNumber']);
        $this->assertIdentical("Could not resolve host: notevenclose.us.oracle.com", $results['requestErrorMessage']);
        $this->assertIdentical(0, $results['responseInfo']['http_code']);

        Helper::setConfigValues($previousValues);
    }

    public function testGeneratePostString() {
        $method = $this->getMethod('generatePostString');

        $this->assertIdentical(array('headfoot', ''), $method(array(), 'head', 'foot'));

        $this->assertIdentical(array('head<data:alice>friend</data:alice><data:bob>foe</data:bob>foot', '<data:alice>friend</data:alice><data:bob>foe</data:bob>'), $method(array('alice' => 'friend', 'bob' => 'foe'), 'head', 'foot'));
    }

    public function testGetOptions(){
        $method = $this->getMethod('getOptions');

        $previousValues = Helper::getConfigValues(array('SIEBEL_EAI_VALIDATE_CERTIFICATE', 'USE_KNOWN_ROOT_CAS'));
        Helper::setConfigValues(array('SIEBEL_EAI_VALIDATE_CERTIFICATE' => false, 'USE_KNOWN_ROOT_CAS' => false));

        $this->assertIdentical(
            array(
                CURLOPT_URL => 'http://nonsecure/stuff',
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_HTTPHEADER => array(
                    "Host: nonsecure",
                    "SOAPAction: \"action\"",
                    "Content-type: text/xml;charset=\"utf-8\"",
                    "Accept: text/xml",
                    "Cache-Control: no-cache",
                    "Pragma: no-cache",
                    "Content-length: 4",
                ),
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => post,
            ),
            $method('http://nonsecure/stuff', 'action', 'post')
        );

        $this->assertIdentical(
            array(
                CURLOPT_URL => 'https://secure/stuff',
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_HTTPHEADER => array(
                    "Host: secure",
                    "SOAPAction: \"action\"",
                    "Content-type: text/xml;charset=\"utf-8\"",
                    "Accept: text/xml",
                    "Cache-Control: no-cache",
                    "Pragma: no-cache",
                    "Content-length: 4",
                ),
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => post,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => false,
            ),
            $method('https://secure/stuff', 'action', 'post')
        );

        Helper::setConfigValues($previousValues);
    }

    public function testGetSecureOptions(){
        $method = $this->getMethod('getSecureOptions');

        $previousValues = Helper::getConfigValues(array('SIEBEL_EAI_VALIDATE_CERTIFICATE', 'USE_KNOWN_ROOT_CAS'));

        Helper::setConfigValues(array('SIEBEL_EAI_VALIDATE_CERTIFICATE' => false, 'USE_KNOWN_ROOT_CAS' => true));
        $this->assertIdentical(
            array(
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => false,
            ),
            $method()
        );

        Helper::setConfigValues(array('SIEBEL_EAI_VALIDATE_CERTIFICATE' => false, 'USE_KNOWN_ROOT_CAS' => false));
        $this->assertIdentical(
            array(
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => false,
            ),
            $method()
        );

        Helper::setConfigValues(array('SIEBEL_EAI_VALIDATE_CERTIFICATE' => true, 'USE_KNOWN_ROOT_CAS' => true));
        $this->assertIdentical(
            array(
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAPATH => Api::cert_path() . '/.ca_hashed_pem',
                CURLOPT_CAINFO => Api::cert_path() . '/ca.pem',
            ),
            $method()
        );

        Helper::setConfigValues(array('SIEBEL_EAI_VALIDATE_CERTIFICATE' => true, 'USE_KNOWN_ROOT_CAS' => false));
        $this->assertIdentical(
            array(
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAPATH => Api::cert_path() . '/.ca_hashed_pem',
            ),
            $method()
        );

        Helper::setConfigValues($previousValues);
    }
}
