<?php

namespace RightNow\Internal;
use RightNow\Api as Log,
    RightNow\Utils\Config,
    RightNow\ActionCapture;

require_once CORE_FILES . 'compatibility/Internal/Sql/Okcs.php';

/**
 * Handles Okcs Saml based IdP intiated SSO authentication
 */
class OkcsSamlAuth {

    /**
    * Constants Http Status Codes
    */
    const MOVED_PERMANENTLY = 301;
    const FOUND = 302;
    const BAD_REQUEST = 400;

    /**
    * Returns a token from OKCS
    * @param string $tokenHeader User Token
    * @return A string|null. A string token from OKCS which can be used for subsequent OKCS Rest calls,
    * or null if no user is logged in or some error happened while performing IdP Init SSO
    */
    public static function authenticate($tokenHeader) {
        $session = get_instance()->session;
        $okcsToken = null;
        if($session->isLoggedIn()) {
            Log::phpoutlog("RightNow\compatibility\Internal\OkcsSamlAuth:INFO Valid user session exists");
            $startTime = microtime(true);
            $spDetails = \RightNow\Internal\Sql\Okcs::getOkcsServiceProviderDetails();
            $stopTime = microtime(true);
            $duration = $stopTime - $startTime;
            ActionCapture::instrument('Authenticate', 'getOkcsSPDetails', 'info', array('ServiceProviderDetails' => json_encode($spDetails)), $duration);
            if(self::validateServiceProviderDetails($spDetails)) {
                if(self::checkEnabled($spDetails)) {
                    $startTime = microtime(true);
                    $tokenResult = self::getSamlToken($spDetails);
                    $stopTime = microtime(true);
                    $duration = $stopTime - $startTime;
                    ActionCapture::instrument('Authenticate', 'getSamlToken', 'info', array('ServiceProviderDetails' => json_encode($spDetails), 'tokenResult' => json_encode($tokenResult)), $duration);
                    if($tokenResult['token'] === null) {
                        Log::phpoutlog("RightNow\compatibility\Internal\OkcsSamlAuth:WARN SAML token generation failed with error message: " . $tokenResult['result']);
                    }
                    $baseEncoded = base64_encode($tokenResult['token']);
                    $postData = "SAMLResponse=" . urlencode($baseEncoded) . "&RelayState=" . urlencode($spDetails['app_url']);
                    $startTime = microtime(true);
                    $content = self::performIdpInitiatedSso($postData, $spDetails, $tokenHeader);
                    $stopTime = microtime(true);
                    $duration = $stopTime - $startTime;
                    ActionCapture::instrument('Authenticate', 'performIdpInitiatedSso', 'info', array('ServiceProviderDetails' => json_encode($spDetails)), $duration);
                    $httpBody = self::getContentBody($content);
                    $okcsToken = self::parseOkcsToken($httpBody);
                }
                else {
                    Log::phpoutlog("RightNow\compatibility\Internal\OkcsSamlAuth:WARN: Okcs SP or App disabled");
                }
            }
            else {
                Log::phpoutlog("RightNow\compatibility\Internal\OkcsSamlAuth:WARN: URL(s) not present in  SP Details");
            }
        }
        else {
            Log::phpoutlog("RightNow\compatibility\Internal\OkcsSamlAuth:INFO: No User logged in, returning null");
        }
        Log::phpoutlog("RightNow\compatibility\Internal\OkcsSamlAuth:INFO: Returning Okcs Token: " . $okcsToken);
        return $okcsToken;
    }

    /**
    * Returns a parsed token in the appropriate form from http body 
    * @param string $httpBody A String which holds http body that need to be parsed.
    * @return a parsed token in the appropriate form from http body 
    */
    public static function parseOkcsToken($httpBody) {
        //TODO: To be filled by OKCS widget team to get the parsed form of token form the http body 
        return $httpBody;
    }

    /**
    * Return SAML token generate by C layer
    * @param array $spDetails An array of service provider data
    * @return Boolean True if sp_enabled and app_enabled are set otherwise false
    */ 
    private static function checkEnabled(array $spDetails) {
        return $spDetails['sp_enabled'] && $spDetails['app_enabled'];
    }

    /**
    * Return SAML token generate by C layer
    * @param array $spDetails An array of service provider data
    * @return boolean, True if array doesn't contain any null value otherwise False 
    */ 
    private static function validateServiceProviderDetails(array $spDetails) {
        return ( $spDetails['app_url'] !== null ) && ( $spDetails['sp_acs_url'] !== null ) && ( $spDetails['sp_id'] !== null );
    }

    /**
    * Returns content received, after performing IDP initiated SSO
    * @param String $postData URL encoded string.
    * @param array $spDetails Array of service provider details.
    * @param string $tokenHeader User Token
    * @return String A response string on the basis of status code.
    */
    private static function performIdpInitiatedSso($postData, $spDetails, $tokenHeader) {
        if(!extension_loaded('curl') && !@Log::load_curl()) {
            Log::phpoutlog("RightNow\compatibility\Internal\OkcsSamlAuth:WARN Failed to Load cURL");
            return null;
        }
        $options = array(
            CURLOPT_URL => $spDetails['sp_acs_url'],
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 1,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_MAX_DEFAULT,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_COOKIE => '',
            CURLOPT_HTTPHEADER => array()
        );
        $acsResponse = self::makeCurlRequest($options);
        preg_match_all('|Set-Cookie: (.*);|U', $acsResponse, $cookie);
        $cookies = implode(';', $cookie[1]);
        $options[CURLOPT_COOKIE] = $cookies;
        $options[CURLOPT_URL] = Config::getConfig(OKCS_IM_APP_URL) . '/wa/generateAPIToken';
        $options[CURLOPT_HTTPHEADER] = array('integrationUserToken: ' . $tokenHeader);
        return self::makeCurlRequest($options);
    }

    /**
    * Method to call APIs through curl
    * @param array $options Array of curl request options
    * @return object API response object
    */
    private static function makeCurlRequest(array $options) {
        if (!extension_loaded('curl') && !@Api::load_curl())
            return null;
        $ch = curl_init();
        if(curl_errno($ch)) {
            Log::phpoutlog("RightNow\compatibility\Internal\OkcsSamlAuth:WARN: cURL invocation failed with error_code: ", curl_errno($ch) . ' and error message: ' . curl_error($ch));
        }
        curl_setopt_array($ch, $options);
        //To avoid 'sleeping processes' occupying a db connection, close the connection prior to making any request to the OKCS API
        //Forcing commit before disconnecting
        $hooks = &load_class('Hooks');
        $hooks->_run_hook(array(
                            'class' => 'RightNow\Hooks\SqlMailCommit',
                            'function' => 'commit',
                            'filename' => 'SqlMailCommit.php',
                            'filepath' => 'Hooks'
                        ));
        //closing the open connection.
        Api::sql_disconnect();
        $content = @curl_exec($ch);
        //Open database connection for further use.
        Api::sql_open_db();
        curl_close($ch);
        return $content;
    }

    /**
    * Return SAML token generate by C layer
    * @param array $spDetails An array of service provider data
    * @return TokenData Array which contains token details
    */ 
    private static function getSamlToken(array $spDetails) {
        $samlParams = array(
            'provider_id' => $spDetails['sp_id'],
            'subject' => SSO_TOKEN_SUBJECT_LOGIN
        );
        return sso_contact_saml_sp_response_generate($samlParams);
    }

    /**
    * Return HttpBody from the content
    * @param String $content A string which contains Http content
    * @return String Httpbody from the $content which holds HTML response data as a string.
    */
    private static function getContentBody($content) {
        $httpContentArr = explode("\r\n\r\n", $content, 3);
        return $httpContentArr[count($httpContentArr) - 1];
    }
}
