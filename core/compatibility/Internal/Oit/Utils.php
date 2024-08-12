<?php
namespace RightNow\Internal\Oit;

use RightNow\Internal\Api as CoreApi,
    RightNow\Api,
    RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Internal\Utils\Version as Version,
    RightNow\Connect\Crypto\v1_4 as Crypto;

final class Utils {
    /**
     * Authenticate a chat for the current user and return the JWT they need to establish the chat
     * @param array $chatType Can be 2 for video chat or 1 for any other chat
     * @return array Contains'jwt', a string
     */
    public static function processAuthenticateChat($chatType) {
        // first we get the JWT
        $chatData = self::getChatAuthenticationData($chatType)->result;

        if (!$chatData || !$chatData['jwt'])
            return self::getResponseObject(null, null, \RightNow\Utils\Config::getConfig(APP_ERROR_LBL));

        // next we assemble the metadata necessary to authenticate
        // we send this to the chat server on the server so an attacker cannot edit the request
        $message = self::getChatAuthenticationMessage($chatData)->result;

        // then we authenticate with the chat server, which registers the JWT as a single-user token
        $response = json_decode(self::makeChatRequestHelper(self::getChatUrl(array(), true), null, false, false, $message, $chatData['jwt']));

        if (!$response) {
            return self::getResponseObject(null, null, \RightNow\Utils\Config::getConfig(APP_ERROR_LBL));
        }

        // finally, return the JWT so the browser can initiate the chat session
        return self::getResponseObject(array('jwt' => $chatData['jwt'], 'pool' => $response->pool), 'is_array');
    }

    /**
     * Get the data necessary to authenticate a chat (JWT and tier 1 session ID - different than CP's session ID)
     * @param array $chatType Can be 2 for video chat or 1 for any other chat
     * @return array Contains'jwt' and 'tier1SessionId', both strings
     */
    public static function getChatAuthenticationData($chatType) {
        $apiResult = CoreApi::chat_contact_auth_generate($chatType);

        if (!$apiResult || !$apiResult['session'] || !$apiResult['jtg_rv']) {
            // there is not a use case where we don't get a JWT back, so we don't know what caused it
            // Note that $apiResult['error_code'] should be populated here, but that still doesn't tell us
            // which message to display
            return self::getResponseObject(null, null, \RightNow\Utils\Config::getConfig(APP_ERROR_LBL));
        }

        $result = $apiResult['jtg_rv'];
        $result['tierOneSessionId'] = $apiResult['session'];
        return self::getResponseObject($result);
    }

    /**
     * Builds up the chat authentication message, which is just JSON containing some information about the logged in user and the current interface
     * @param array $chatData An array of data for chat message
     * @return string JSON representation of the message header
     */
    public static function getChatAuthenticationMessage($chatData) {
        $contactId = get_instance()->session->getProfileData('contactID');
        $profile = get_instance()->session->getProfile(true);
        $sessionId = get_instance()->session->getSessionData('sessionID');

        $metadata = array(
            "firstName" => $chatData['first_name'],
            "lastName" => $chatData['last_name'],
            "emailAddress" => $chatData['email'],
            "interfaceId" => Api::intf_id(),
            "contactId" => $contactId,
            "organizationId" => null,
            "productId" => null,
            "categoryId" => null,
            "tierOneSessionId" => $chatData['tierOneSessionId'],
            "queueId" => null,
            "incidentId" => null,
            "resumeType" => "NONE",
            "question" => null, // subject of chat
            "coBrowsePremiumSupported" => null,
            "mediaList" => array(),
            "customFields" => array(),
            "requestSource" => null,
            "sessionId" => $sessionId,
            "auxiliaryData" => null
        );

        if($profile !== null)
        {
            $contactId = $profile->contactId;
            $organizationID = $profile->orgID;

            if(is_int($organizationID) && $organizationID > 0)
                $metadata['organizationId'] = $organizationID;
        }
        $metadata['requestSource'] = $_POST['requestSource'];
        foreach($_POST as $key => $value)
        {
            if($key === 'prod')
            {
                $metadata['productId'] = $value;
            }
            else if($key === 'cat')
            {
                $metadata['categoryId'] = $value;
            }
            else if($key === 'subject')
            {
                $metadata['question'] = html_entity_decode($value);
            }
            else if($key === 'incidentID')
            {
                $metadata['incidentId'] = $value;
            }
            else if($key === 'resume')
            {
                $metadata['resumeType'] = ($value == 'true') ? 'RESUME' : 'DO_NOT_RESUME';
            }
            else if($key === 'queueID')
            {
                $metadata['queueId'] = $value;
            }
            else if($key === 'coBrowsePremiumSupported' || $key === 'referrerUrl' || $key === 'routingData')
            {
                $metadata[$key] = $value;
            }
            else if($key === 'mediaList')
            {
                $metadata['mediaList'] = json_decode($value);
            }
            else if($key === 'customFields')
            {
                foreach (json_decode($value) as $fieldName => $fieldValue) {
                    $metadata['customFields'][] = array(
                        'name' => $fieldName,
                        'value' => html_entity_decode($fieldValue)
                    );
                }
            }
            else if($key === 'auxiliaryData')
            {
                $metadata['auxiliaryData'] = json_decode($value);
            }
            else if($profile === null) // Only use POSTed contact info if not logged in
            {
                if($key === 'email')
                    $metadata['emailAddress'] = $value;
                else if($key === 'firstName')
                    $metadata['firstName'] = html_entity_decode($value);
                else if($key === 'lastName')
                    $metadata['lastName'] = html_entity_decode($value);
            }
        }

        return self::getResponseObject($metadata);
    }

    /**
     * Utility function to build up a web request to the chat server
     * @param string $url The URL to make the request to
     * @param string $jsessionID The session ID that identifies a given session
     * @param bool $isCacheable Indicates if the request can be satisfied with cached data and if the response is suitable for caching.
     * @param bool $allowTimeout If true, the request will timeout after 5 seconds.
     * @param array|null $postData If populated, the contents will beent as post data with the request.  This will force a POST request.
     * @param array|null $jwt The JWT (JSON Web Token) to pass in the Authentication header. If provided, adds the authentication
     *   header and a 'Content-type: application/json' header. Usually only used before initiating a chat.
     * @return string The response from the equest
     */
    public static function makeChatRequestHelper($url, $jsessionID = null, $isCacheable = false, $allowTimeout = false, $postData = null, $jwt = null) {
        $requester = function() use($url, $jsessionID, $allowTimeout, $postData, $jwt) {
            //To avoid 'sleeping processes' occupying a db connection, close the connection prior to making any request to the chat service 
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
            $CI = get_instance();

            // set the headers - but don't leave any blank lines or that will mess up subsequent headers
            $options = array("User-Agent: {$CI->input->user_agent()}",
                             "X-Forwarded-For: {$CI->input->ip_address()}");

            if ($jsessionID !== null)
                $options[] = "Cookie: JSESSIONID=$jsessionID";

            // if we have a JWT, set up the headers for the Chat REST API
            if ($jwt) {
                $options[] = "Authorization: Bearer {$jwt}";
                $options[] = 'Content-Type: application/json; charset=utf-8';
                $options[] = 'Accept: application/json; charset=utf-8';
            }

            //Use curl if request is over SSL
            $useSSL = Text::beginsWithCaseInsensitive($url, 'https');
            if($useSSL && !@Api::load_curl())
            {
                //failed to load curl. we will log this and proceed request using http
                $useSSL = false;
            }

            if (!$postData['contactId'])
                $postData['contactId'] = 0;
            $data = json_encode($postData);

            if($useSSL)
            {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $options);
                if (\RightNow\Utils\Config::getConfig(GLOBAL_SSL_COMMUNICATION_ENABLED))
                {
                    $dbPath = substr(Api::cert_path(), 0, strrpos(Api::cert_path(), '/'));
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
                    curl_setopt($ch, CURLOPT_CAINFO, $dbPath . '/prod_certs/ca.pem');
                    curl_setopt($ch, CURLOPT_CAPATH, $dbPath . '/prod_certs/.ca_hashed_pem');
                    curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_3);                    
                }else{
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                }

                if($allowTimeout)
                    curl_setopt($ch, CURLOPT_TIMEOUT, 4);

                if ($data)
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                // Issue request to server, handling any potential PHP error by falling back to setting the response to false.
                // Note (here and below in file_get_contents) that we use "or" instead of "||". Apparently this is necessary
                // to properly handle PHP errors. "||" results in the assignment of 1 to $response regardless of success.
                ($response = @curl_exec($ch)) || ($response = false);
            }
            else
            {
                $contextOptions = array('http' => array('header' => implode("\r\n", $options)));

                // For reasons that I do not understand, real-world use of the timeout value is actually double
                // of what's specified in this context option. So this is effectively 4 seconds and not really 2.
                if($allowTimeout)
                    $contextOptions['http']['timeout'] = 2;

                // if we have a JWT, we need to do this with POST
                if ($jwt) {
                    $contextOptions['http']['method'] = 'POST';
                    $contextOptions['http']['content'] = $data;
                    $contextOptions['http']['header'] .= "\r\nContent-Length: " . strlen($data);
                }

                $context = stream_context_get_default($contextOptions);
                ($response = @file_get_contents($url, false, $context)) || ($response = false);
            }
            //Always open database connection since we can only be sure we can keep the database closed with syndicated widget connections which are only in CPv2
            Api::sql_open_db();

            return $response;
        };

        if($isCacheable)
        {
            $cache = new \RightNow\Libraries\Cache\PersistentReadThroughCache(5, $requester);
            try {
                return $cache->get($url);
            }
            catch (\Exception $e) {
                //Cache check fails, no need to do anything special
            }
        }
        return $requester();
    }

    /**
     * Replaces previous function called getChatServerHostAndPath. This will
     * build a full url to the chat server and include query parameters that are
     * provided.
     * @param array|null $queryParameters Key/Value array of parameters to include
     * @param boolean $isRest Indicates whether to use the newerChat REST API
     * @return string The full url
     */
    public static function getChatUrl($queryParameters = array(), $isRest = false) {
        // Create a pool query parameter if pool id has a value
        $poolID = \RightNow\Utils\Config::getConfig(CHAT_CLUSTER_POOL_ID);
        if($poolID)
            $queryParameters['pool'] = $poolID;

        $dbName = \RightNow\Utils\Config::getConfig(DB_NAME);

        // First get the internal host if it exists
        $chatServerHost = \RightNow\Utils\Config::getConfig(SRV_CHAT_INT_HOST);
        // Now create the url base
        if(!$chatServerHost)
        {
            // If SRV_CHAT_INT_HOST was empty then use SRV_CHAT_HOST. That implies the request
            // will go back into the wild in which case we need to ensure we use SSL if the
            // original request was in SSL.
            $chatUrlBase = (self::isRequestHttps() ? ('https://') : ('http://')) . \RightNow\Utils\Config::getConfig(SRV_CHAT_HOST);
        }
        else
        {
            if (\RightNow\Utils\Config::getConfig(GLOBAL_SSL_COMMUNICATION_ENABLED))
                $chatUrlBase = "https://$chatServerHost";
            else
                $chatUrlBase = "http://$chatServerHost";
        }

        // Now build the url (minus the query string)
        if ($isRest)
            $chatUrl = "$chatUrlBase/engagement/api/consumer/$dbName/v1/authenticate";
        else
            $chatUrl = "$chatUrlBase/Chat/chat/$dbName";

        // And finally add the query parameters
        if(count($queryParameters) > 0)
        {
            $chatUrl = "$chatUrl?" . http_build_query($queryParameters);
        }
        return $chatUrl;
    }

    /**
     * Wrapper for ResponseObject class
     * @param mixed $return The object|array|bool|string|whatever that is being returned from the method that was called.
     * @param \Closure|string $validationFunction A callable function that takes the return value as it's sole argument and returns
     * true upon success. If specified as null, no validation is performed.
     * @param array|string $errors An array of error messages, or a ResponseError objects or a string message
     * @param array|string $warnings An array of warning messages or a string message
     * @return \RightNow\Libraries\ResponseObject Instance of ResponseObject with populated properties
     * @see    \RightNow\Libraries\ResponseObject Used to construct one of these
     */
    public static function getResponseObject($return, $validationFunction = 'is_object', $errors = array(), $warnings = array()) {
        $response = new \RightNow\Libraries\ResponseObject($validationFunction);
        $response->result = $return;

        if(!is_array($errors)){
            $errors = $errors ? array($errors) : array();
        }
        if(!is_array($warnings)){
            $warnings = $warnings ? array($warnings) : array();
        }
        foreach($errors as $error) {
            $response->error = $error;
        }
        foreach ($warnings as $warning) {
            $response->warning = $warning;
        }
        return $response;
    }

    /**
     * Create SHA-256 hash of a string
     * @param string $message The string to be hashed
     * @return string SHA-256 hash of the string passed
     */
    public static function getSHA2Hash($message) {
        try {
            if(Version::compareVersionNumbers(Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.8") >= 0)
               $md = new Crypto\MessageDigest();
            else
               $md = new \RightNow\Connect\Crypto\v1_3\MessageDigest();
            $md->Algorithm->ID = 3; //SHA256
            $md->Text = $message;
            $md->Encoding->ID = 1;
            $md->hash();
            $hashedText = $md->HashText;
            return bin2hex($hashedText);
        }
        catch(Exception $err) {
            echo $err->getMessage();
        }
    }

    /**
     * Indicates if the current request was made over HTTPS.
     * @return bool Whether or not the current request was made over HTTPS
     */
    public static function isRequestHttps() {
        return (isset($_SERVER['HTTP_RNT_SSL']) && $_SERVER['HTTP_RNT_SSL'] === 'yes') || (!IS_HOSTED && $_SERVER['HTTPS'] === 'on');
    }

    /**
     * Returns the current URL including the Protocol, Server and Request URI
     *
     * @param bool $includeUri Whether or not to include request URI as well as hostname
     * @return string The current URL
     */
    public static function getOriginalUrl($includeUri = true) {
        $protocol = self::isRequestHttps() ? 'https' : 'http';
        return "$protocol://{$_SERVER['SERVER_NAME']}" . ($includeUri ? ORIGINAL_REQUEST_URI : '');
    }

    /**
     * Generate a token used to validate the constraints of the submitted form data. This token enables server side validation
     * of widget attribute constraints. It's salted with the POST action (e.g. /app/ask), interface name and the post handler to prevent
     * the token from being used on other sites or forms or with different handler.
     * @param string $constraints A base64 encoded string of widget attribute constraints
     * @param string $action The POST action receiving the token
     * @param string $handler The PostRequest method that will handle the POST data
     * @return string A token combining the constraints, interface, and action
     */
    public static function createPostToken($constraints, $action, $handler) {
        $action = Url::deleteParameter($action, 'session');
        return Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe(implode('|', array(sha1($constraints), Api::intf_name(), $action, $handler))));
    }

    /**
    * Sends the appropriate response headers for a CORS requests.
    * @param array $allowedMethods HTTP methods to be allowed for CORS requests
    * @param boolean $allowedOrigin Origin opened for CORS request
    * @param int $cacheTime The total seconds an actual response
    * should be cached for
    */
    public static function sendCORSHeaders(array $allowedMethods, $allowedOrigin = '', $cacheTime = 12) {
        if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            //cache OPTIONS requests for 24 hours
            header("Access-Control-Max-Age: " . 86400);
            header("Access-Control-Allow-Headers: RNT_REFERRER,X-Requested-With");
            header("Access-Control-Allow-Methods: ". implode(', ', $allowedMethods));
        }
        else if($cacheTime !== 0) {
            //cache GET/POST requests for the given amount of time
            header("Expires: " . gmdate('D, d M Y H:i:s', time() + $cacheTime) . "GMT");
        }
        $allowedOrigins = !empty($allowedOrigin) ? $allowedOrigin : \RightNow\Utils\Url::getShortEufBaseUrl('sameAsRequest');
        header("Access-Control-Allow-Origin: ". $allowedOrigins);
        header("Access-Control-Allow-Credentials: true");
        header("Vary: Origin");
    }

    /**
     * Determines whether the requesting domain is allowed to call inlay endpoint.
     * @return boolean $domainAllowed
     */
    public static function isValidRequestDomain() {
        $allowedDomains = Config::getConfig(OIT_CORS_ALLOWLIST);
        $domainAllowed = false;

        if($allowedDomains && (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN']) && preg_match('/' . $allowedDomains . '/', $_SERVER['HTTP_ORIGIN'])) {
            $domainAllowed = true; //domain allowed as origin matches
        } else if($allowedDomains && (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN']) && !preg_match('/' . $allowedDomains . '/', $_SERVER['HTTP_ORIGIN'])) {
            $domainAllowed = false; //domain not allowed as origin doesn't match
        } else if($allowedDomains && (!isset($_SERVER['HTTP_ORIGIN']) || !$_SERVER['HTTP_ORIGIN']) && (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) && preg_match('/' . $allowedDomains . '/', $_SERVER['HTTP_REFERER'])) {
            $domainAllowed = true; //domain allowed as referrer matches. This is required when you disable security in chrome.
        } else if($allowedDomains && (!isset($_SERVER['HTTP_ORIGIN']) || !$_SERVER['HTTP_ORIGIN']) && (!isset($_SERVER['HTTP_REFERER']) || !$_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_HOST']
            && preg_match('/' . $_SERVER['HTTP_HOST'] . '/', \RightNow\Utils\Url::getShortEufBaseUrl('sameAsRequest'))
            && preg_match('/' . $allowedDomains . '/', $_SERVER['HTTP_HOST'])) {
            $domainAllowed = true; //domain same as CP host
        }
        return $domainAllowed;
    }

    /**
     * Returns allowed origin for CORS
     * @return string Origin allowed for CORS
     */
    public static function getAllowedOrigin() {
        $parsedReferer = $extractedOrigin = '';
        // If origin is empty, then check if the referrer is present. This is required when you disable security in chrome.
        // Function isValidRequestDomain must have be called before reaching this stage
        if(!$_SERVER['HTTP_ORIGIN']) {
            $parsedReferer = parse_url($_SERVER['HTTP_REFERER']);
            $extractedOrigin = str_replace($parsedReferer['path'], '', $_SERVER['HTTP_REFERER']);
        }
        return ($_SERVER['HTTP_ORIGIN'] ? $_SERVER['HTTP_ORIGIN'] : $extractedOrigin);
    }

    /**
     * Checks the given filename for bad characters and adjusts as necessary, returning the potentially modified file name.
     * @param string $filename Name of the uploaded file
     * @return string Sanitized file name
     */
    public static function sanitizeFilename($filename) {
        // The name shouldn't have tag delimiters but it also
        // should not have single or double quotes in it to prevent
        // denial of service attacks.

        if(preg_match("@(<|&lt;).*(>|&gt;)@i", $filename))
        {
            $rnow = get_instance()->rnow; // use get_instance for unit testing
            $filename = strtr($filename, $rnow->getFileNameEscapeCharacters());
        }

        $filename = preg_replace("/[\r\n\/:*?|]+/", '_', $filename);
        return strtr($filename, "\"'", "--");
    }

    /**
     * Echoes out the JSON encoded value of $toRender.  The `text/plain` content type header is used
     * since YUI uploads expect a document to be returned.
     * @param (Object|Array) $toRender The data to encode
     */
    public static function renderJSON($toRender) {
        $content = json_encode($toRender);
        header('Content-Length: ' . strlen("$content"));
        header('Content-Type: text/plain');
        echo $content;
    }

    /**
     * Echoes a JSON-encoded error object. May contain errorMessage or error keys.
     * @param string $errorMessage Error message
     * @param int $errorCode Error code
     * @return json Upload error in json format
     */
    public static function uploadError($errorMessage, $errorCode = null)
    {
        $uploadFailure = array('errorMessage' => $errorMessage);
        if($errorCode)
        {
            $uploadFailure['error'] = $errorCode;
        }
        return self::renderJSON($uploadFailure);
    }

    /**
     * Downloads FAS temp file
     * @param object $fileMetadata File metadata
     * @return mixed Requested file or error
     */
    public static function downloadFromTempLocation($fileMetadata) {
        $errorResponse = array("errors" => array());
        if (!$fileMetadata || !$fileMetadata->localFileName) {
            header('HTTP/1.1 400 Bad Request');
            $errorResponse["errors"] = array(array("status" => 400, "detail" => "Invalid file data"));
            self::renderJSON($errorResponse);
            return;
        }
        if (!Api::fas_enabled() || !Api::fas_has_tmp_file($fileMetadata->localFileName) || Api::fas_get_tmp_filesize($fileMetadata->localFileName) != $fileMetadata->fileSize) {
            header('HTTP/1.1 500 Internal Server Error');
            $errorResponse["errors"] = array(array("status" => 500, "detail" => "An unknown error has occurred"));
            self::renderJSON($errorResponse);
            return;
        }
        self::_sendContent($fileMetadata);
    }

    /**
     * Sends the file content to the client.
     * @param object $fileMetadata File metadata
     */
    private function _sendContent($fileMetadata) {
        header("Content-Disposition: attachment; filename=\"" . $fileMetadata->userFileName . "\"");
        header(gmstrftime('Date: %a, %d %b %Y %H:%M:%S GMT'));
        header('Accept-Ranges: none');
        header("Content-Type: application/octet-stream");
        header("Cache-Control: no-store");

        \RightNow\Utils\Framework::killAllOutputBuffering();
        self::_stream('fas_get_tmp_file', $fileMetadata->localFileName, $fileMetadata->contentType);
    }

    /**
     * Streams the designated file.
     * @param string $streamFunction Name of the function. Should either
     *  be readfile, fas_get, or fas_get_tmp_file
     * @param string $fileName Name of the file to stream
     * @param string $contentType The content/mimetype of the file to stream
     */
    private function _stream($streamFunction, $fileName, $contentType = null) {
        if ($contentType === 'text/html') {
            ob_start('RightNow\Utils\Text::escapeHtml', 16384);
        }

        if (method_exists('\RightNow\Api', $streamFunction)) {
            Api::$streamFunction($fileName);
        }
        else {
            $streamFunction($fileName);
        }
    }
}
