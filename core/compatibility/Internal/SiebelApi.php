<?php
namespace RightNow\Internal;

use RightNow\Utils\Config;

final class SiebelApi
{
    /**
     * Return components necessary to make a Siebel request.
     *
     * @return array An array having keys (all string types):
     *   'siebelUrl'
     *   'soapAction'
     *   'requestHeader'
     *   'requestFooter'
     */
    public static function generateRequestParts()
    {
        $siebelUrl = 'https://' . Config::getConfig(SIEBEL_EAI_HOST) . '/eai_' . Config::getConfig(SIEBEL_EAI_LANGUAGE) . '/start.swe?SWEExtSource=SecureWebService&SWEExtCmd=Execute&WSSOAP=1';
        $siebelAdmin = Config::getConfig(SIEBEL_EAI_USERNAME);
        $siebelPassword = Config::getConfig(SIEBEL_EAI_PASSWORD);

        $requestHeader = <<<HEADER
<soapenv:Envelope xmlns:soapenv='http://schemas.xmlsoap.org/soap/envelope/' xmlns:ser='http://siebel.com/Service/FS/ServiceRequests' xmlns:data='http://www.siebel.com/xml/Service%20Request/Data' xmlns:ws='http://siebel.com/webservices'>
    <soapenv:Header>
        <ws:UsernameToken>$siebelAdmin</ws:UsernameToken>
        <ws:PasswordText>$siebelPassword</ws:PasswordText>
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

        $soapAction = 'document/http://siebel.com/Service/FS/ServiceRequests:ServiceRequestInsert';

        return array('siebelUrl' => $siebelUrl, 'soapAction' => $soapAction, 'requestHeader' => $requestHeader, 'requestFooter' => $requestFooter);
    }

    /**
     * Return components necessary to make a Siebel request.
     *
     * @param array $siebelData Array of key-value pairs to put in the body of the request
     * @param string $siebelUrl URL to Siebel instance
     * @param string $soapAction SOAP action to put in the request
     * @param string $requestHeader POST data to include before the body of the request
     * @param string $requestFooter POST data to include after the body of the request
     * @return array|null Null if cURL could not be loaded, otherwise an array having keys:
     *   'success' bool True if the response indicates success
     *   'response' string Response from the cURL request
     *   'requestBody' string Body of the request
     *   'requestErrorNumber' int Error code while making the request, if any
     *   'requestErrorMessage' string Error message while making the request, if any
     *   'responseInfo' array Information related to the response
     * @throws \Exception If cURL cannot be initialized
     */
    public static function makeRequest(array $siebelData, $siebelUrl, $soapAction, $requestHeader, $requestFooter)
    {
        if(!extension_loaded('curl') && !@Api::load_curl())
            throw new \Exception('Loading of cURL failed.');

        list ($postString, $requestBody) = self::generatePostString($siebelData, $requestHeader, $requestFooter);

        $ch = curl_init();
        if (!$ch)
            throw new \Exception('Initialization of cURL failed.');

        $options = self::getOptions($siebelUrl, $soapAction, $postString);

        curl_setopt_array($ch, $options);
        $response = @curl_exec($ch);

        $requestErrorNumber = curl_errno($ch);
        $requestErrorMessage = curl_error($ch);
        $responseInfo = curl_getinfo($ch);

        $success = !$requestErrorNumber && !$requestErrorMessage && $responseInfo['http_code'] === 200 && preg_match('#<ServiceRequest><Id>[A-Z0-9-]+</Id>#', $response);

        return array('success' => $success, 'response' => $response, 'requestBody' => $requestBody,
            'requestErrorNumber' => $requestErrorNumber, 'requestErrorMessage' => $requestErrorMessage, 'responseInfo' => $responseInfo);
    }

    /**
     * Return array of options to use with cURL for every request.
     *
     * @param array $siebelData Array of key-value pairs to put in the body of the request
     * @param string $requestHeader POST data to include before the body of the request
     * @param string $requestFooter POST data to include after the body of the request
     * @return array Array with the first element being the entire postString and the second element containing the portion of the request that is based on end user input
     */
    private static function generatePostString(array $siebelData, $requestHeader, $requestFooter)
    {
        $requestBody = '';
        foreach ($siebelData as $key => $value) {
            $requestBody .= "<data:$key>$value</data:$key>";
        }

        $postString = $requestHeader . $requestBody . $requestFooter;

        return array($postString, $requestBody);
    }

    /**
     * Return array of options to use with cURL.
     *
     * @param string $siebelUrl URL to Siebel instance
     * @param string $soapAction SOAP action to put in the request
     * @param string $postString POST string
     * @return array Array of options to use in the cURL request
     */
    private static function getOptions($siebelUrl, $soapAction, $postString)
    {
        $siebelUrlParts = @parse_url($siebelUrl);
        $protocol = $siebelUrlParts['scheme'];
        $host = $siebelUrlParts['host'];

        $options = array(
            CURLOPT_URL => $siebelUrl,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => array(
                "Host: $host",
                "SOAPAction: \"$soapAction\"",
                "Content-type: text/xml;charset=\"utf-8\"",
                "Accept: text/xml",
                "Cache-Control: no-cache",
                "Pragma: no-cache",
                "Content-length: " . strlen($postString),
            ),
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $postString,
        );

        if($protocol === 'https')
        {
            $options += self::getSecureOptions();
        }

        return $options;
    }

    /**
     * Return array of options to use with https-related options added.
     *
     * @return array Array of options to use in the cURL request, including any related to https
     */
    private static function getSecureOptions()
    {
        $validation = Config::getConfig(SIEBEL_EAI_VALIDATE_CERTIFICATE);
        if ($validation)
        {
            $options = array(
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAPATH => Api::cert_path() . '/.ca_hashed_pem',
            );
            if (Config::getConfig(USE_KNOWN_ROOT_CAS))
                $options[CURLOPT_CAINFO] = Api::cert_path() . '/ca.pem';
            return $options;
        }
        return array(
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => false,
        );
    }
}
