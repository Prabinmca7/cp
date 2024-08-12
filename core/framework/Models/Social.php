<?php

namespace RightNow\Models;

require_once CPCORE . 'Models/AsyncBase.php';
require_once CPCORE . 'Internal/Libraries/Encryption.php';

use RightNow\Api,
    RightNow\Libraries,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\ActionCapture,
    RightNow\Utils\Framework,
    RightNow\Internal\Libraries\Encryption;

/**
 * Methods to interact with the social API for creating social posts and comments. Results are automatically memcached
 * for performance benefits.
 */
class Social extends AsyncBase
{
    private $communityProtocol;
    private $communityHostname;
    private $apiVersion = COMMUNITY_NOV_10_API_VERSION;

    //Limit all requests to only take up to 5 seconds before bailing
    const REQUEST_TIMEOUT_LENGTH = 5;
    const AUTHENTICATED_USER_ERROR = '35';
    const POST_RESOURCE_NOT_FOUND_ERROR = '4';

    const SSO3_KEY_LENGTH = 16;
    const SSO3_KEY_SALT_LENGTH = 8;
    const SSO3_SIGNATURE_SALT_LENGTH = 8;
    const SSO3_IV_LENGTH = 16;

    public function __construct()
    {
        $communityUrl = @parse_url(Config::getConfig(COMMUNITY_BASE_URL));
        if($communityUrl !== false)
        {
            $this->communityProtocol = \RightNow\Utils\Url::isRequestHttps() ? 'https' : 'http';
            $this->communityHostname = $communityUrl['host'];
        }
        parent::__construct(array('performSearch'));
    }

    /**
     * Executes a search to the community with the arguments specified
     *
     * @param string $keyword The keyword term to search on
     * @param int $limit The limit on the number of results to display
     * @param string $sortFilter The sort filter to apply to the search results
     * @param string $resourceID Resource hash to filter results by
     * @param array|null $userToFilter User to filter results by;either contains a 'userID' or 'userHash' key
     * @param int $start The start index of the first item to return. Index starts with 1 (not 0).
     * @return array An array of results or null if no results
     */
    public function performSearch($keyword='', $limit=5, $sortFilter=null, $resourceID=null, $userToFilter=null, $start=null)
    {
        $responseObject = $this->getResponseObject(null, 'is_null');
        if(!$this->canMakeCommunityApiRequest()){
            $responseObject->error = 'Community config settings are not set properly.';
            return $responseObject;
        }
        $baseUrl = $this->generateCommunityApiUrl(array('Action' => 'Search'));
        if($keyword !== null && $keyword !== false && $keyword !== '')
            $baseUrl .= '&term=' . urlencode($keyword);
        if($limit)
            $baseUrl .= "&limit=$limit";
        if($sortFilter)
            $baseUrl .= "&sort=$sortFilter";
        if($resourceID)
            $baseUrl .= "&hiveHash=$resourceID";
        if($userToFilter)
        {
            if($userToFilter['userID'])
                $baseUrl .= ('&userGuid=' . $userToFilter['userID']);
            else if($userToFilter['userHash'])
                $baseUrl .= ('&userHash=' . $userToFilter['userHash']);
        }
        if($start)
            $baseUrl .= "&start=$start";
        if(Framework::isLoggedIn())
            $baseUrl .= "&c_id=" . $this->CI->session->getProfileData('contactID');

        $resultCalculation = function($response) use($responseObject){
            if($response === false && $response === null){
                $responseObject->error = 'Request to community could not be made or response was empty.';
            }
            else{
                $responseObject->validationFunction = 'is_object';
                $responseObject->result = json_decode($response);
            }
            return $responseObject;
        };
        if($keyword !== null && $keyword !== false && $keyword !== ''){
            ActionCapture::record('community', 'search', $keyword);
        }
        if($this->async === true)
        {
            return array(
                'url' => $baseUrl,
                'host' => $this->communityHostname,
                'callback' => $resultCalculation
            );
        }
        return $resultCalculation($this->makeRequestToCommunity($baseUrl, null, true));
    }

    /**
     * Formats an array of search results converting timestamps, highlighting words
     * and truncating content based on arguments.
     *
     * @param array $resultSet List of search results to process
     * @param int $truncateSize Number of characters to truncate the result clip view content
     * @param boolean $shouldHighlight Denotes if content should be highlighted
     * @param string $keyword The word that should be highlighted
     * @param string $urlOverrideLocation Used to change community URL for results. If left blank, community URL will not be affected. URL will be appended with post hash parameter if specified.
     * @return array The formatted results
     */
    public function formatSearchResults(array $resultSet, $truncateSize, $shouldHighlight, $keyword = null, $urlOverrideLocation = '')
    {
        foreach($resultSet as $result)
        {
            $result->lastActivity = Framework::formatDate($result->lastActivity);
            $result->preview = str_ireplace(array('%3C', '<'), '&lt;', Text::truncateText($result->preview, $truncateSize));
            $result->preview = str_ireplace(array('%3E', '>'), '&gt;', $result->preview);
            $result->name = str_ireplace(array('%3C', '<'), '&lt;', $result->name);
            $result->name = str_ireplace(array('%3E', '>'), '&gt;', $result->name);
            if($shouldHighlight)
            {
                if($keyword === null || $keyword === false)
                {
                    $result->name = Text::emphasizeText($result->name);
                    $result->preview = Text::emphasizeText($result->preview);
                }
                else
                {
                    $result->name = Text::emphasizeText($result->name, array('query' => $keyword));
                    $result->preview = Text::emphasizeText($result->preview, array('query' => $keyword));
                }
            }
            if($urlOverrideLocation !== ''){
                $result->webUrl = $urlOverrideLocation . Text::getSubstringStartingWith($result->webUrl, '/posts/');
            }
        }
        return $this->getResponseObject($resultSet, 'is_array');
    }

    /**
     * Retrieves a community user from the community API for the specified user ID
     * @param array $userIdentifier The user to retrieve; must contain either a 'userHash' or 'contactID' key
     * @return object|null The User object or null if the request was unsuccessful
     */
    public function getCommunityUser(array $userIdentifier)
    {
        if($userIdentifier['contactID'])
            $identifier = array('name' => 'UserGuid', 'value' => $userIdentifier['contactID']);
        else if($userIdentifier['userHash'])
            $identifier = array('name' => 'UserHash', 'value' => $userIdentifier['userHash']);

        if(!$identifier)
            return $this->getResponseObject(null, 'is_null', 'Invalid credential keys in userIdentifier array.');

        return $this->parseCommunityResponse($this->getCommunityObject('UserGet', $identifier, null, null, true), "Could not retrieve User with ID '$identifier'.");
    }

    /**
     * Retrieves a community post from the community API for the specified post ID
     * @param string $postHash The hash of the post to retrieve
     * @return object|null The Post object or null if the request was unsuccessful
     */
    public function getCommunityPost($postHash)
    {
        $response = $this->parseCommunityResponse($this->getCommunityObject('PostGet', array('name' => 'postHash', 'value' => $postHash)), "Could not retrieve Post with ID '$postHash'.");
        if($response->result){
            $response->result->post->created = Framework::formatDate(strtotime($response->result->post->created));
            $response->result->post->lastEdited = Framework::formatDate(strtotime($response->result->post->lastEdited));
        }
        return $response;
    }

    /**
     * Retrieves answer comments from the community API for the specified answer ID
     * @param int $answerID The answer ID for which to retrieve comments
     * @return object|null The Comments object or null if the request was unsuccessful
     */
    public function getAnswerComments($answerID)
    {
        return $this->parseCommunityResponse($this->getCommunityObject('CommentList', array('name' => 'postGuid', 'value' => $answerID), Config::getConfig(COMMUNITY_COMMENT_RESOURCE_ID)), "Could not retrieve AnswerComments for Answer with ID '$answerID'.");
    }

    /**
     * Retrieves post comments from the community API for the specified post Hash
     * @param string $postHash The post hash for which to retrieve comments
     * @return object|null The Comments object or null if the request was unsuccessful
     */
    public function getPostComments($postHash)
    {
        $response = $this->parseCommunityResponse($this->getCommunityObject('CommentList', array('name' => 'postHash', 'value' => $postHash)), "Could not retrieve PostComments for Post with ID '$postHash'.");
        if($response->result && $response->result->comments)
        {
            foreach($response->result->comments as $comment)
            {
                $comment->created = Framework::formatDate($comment->created);
                $comment->lastEdited = Framework::formatDate($comment->lastEdited);
            }
        }
        return $response;
    }

    /**
     * Retrieves post type fields from the community API for the specified post type id
     * @param int $postTypeID The post type id for which to retrieve post type fields
     * @return object|null The PostTypes object or null if the request was unsuccessful
     */
    public function getPostTypeFields($postTypeID)
    {
        return $this->parseCommunityResponse($this->getCommunityObject('PostTypeGet', array('name' => 'postTypeId', 'value' => $postTypeID)), "Could not retrieve PostType with ID '$postTypeID'.");
    }

    /**
     * Generic routing function to perform an action on an answer comment
     * @param int $answerID Answer ID of comments being manipulated
     * @param string $action The action to perform. Either 'delete', 'reply', 'edit', 'rate', or 'flag'.
     * @param object $data The data sent with the action. For comment replies it should contain a 'content' property and for ratings it should contain a 'rating' property.
     * @return array Results from request
     */
    public function performAnswerCommentAction($answerID, $action, $data)
    {
        $commentID = $data->id;
        $resultArray = array('action' => $action, 'id' => $commentID);
        $responseObject = $this->getResponseObject($resultArray, 'is_array');
        if(!Framework::isLoggedIn())
        {
            $responseObject->error = Config::getMessage(LOGGED_PERFORM_ACTIONS_MSG);
            return $responseObject;
        }
        if(!$answerID)
        {
            $responseObject->error = Config::getMessage(NO_ANSWER_WAS_SPECIFIED_ACTION_MSG);
            return $responseObject;
        }

        $apiAction = '';
        $additionalParameters = '';
        $postParameters = '';
        switch(strtolower($action))
        {
            case 'delete':
                $apiAction = 'CommentDelete';
                $additionalParameters = "&postGuid=$answerID&commentId=$commentID";
                ActionCapture::record('answerComment', 'delete', $answerID);
                break;
            case 'reply':
                $apiAction = 'CommentAddAsAnswer';
                $additionalParameters = "&postGuid=$answerID&InterfaceId=" . \RightNow\Api::intf_id() . '&HiveHash=' . Config::getConfig(COMMUNITY_COMMENT_RESOURCE_ID);
                if($data->notify)
                    $additionalParameters .= '&SubscribeUser=1';
                //Send the parentID if we're replying to an existing comment
                if($commentID)
                    $additionalParameters .= "&parentId=$commentID";
                $postParameters = '&payload=<?xml version="1.0"?><comments><comment><value><![CDATA[' . urlencode($data->content) . ']]></value></comment></comments>';
                ActionCapture::record('answerComment', 'create', $answerID);
                break;
            case 'edit':
                $apiAction = 'CommentUpdate';
                $additionalParameters = "&postGuid=$answerID&commentId=$commentID";
                $postParameters = '&payload=<?xml version="1.0"?><comment><value><![CDATA[' . urlencode($data->content) . ']]></value></comment>';
                ActionCapture::record('answerComment', 'edit', $answerID);
                break;
            case 'rate':
                $apiAction = 'CommentRate';
                $additionalParameters = "&commentId=$commentID&RatingValue={$data->rating}";
                $resultArray['rating'] = $data->rating;
                $responseObject->result = $resultArray;
                ActionCapture::record('answerComment', 'rate', $answerID);
                ActionCapture::record('answerComment', 'rated', $data->rating);
                break;
            case 'flag':
                $apiAction = 'CommentFlag';
                $additionalParameters = "&commentId=$commentID";
                ActionCapture::record('answerComment', 'flag', $answerID);
                break;
        }

        $response = $this->makeRequestToCommunity($this->generateCommunityApiUrl(array('Action' => $apiAction)) . $additionalParameters, $postParameters);
        $this->processCommunityResponse($response, 'comment', $responseObject, function($comment) use ($action, $resultArray) {
            $resultArray['id'] = $comment->id;
            if($action !== 'delete' && intval($comment->status) !== COMMENT_STATUS_ACTIVE)
                $resultArray['message'] = Config::getMessage(COMMENT_COMMENT_UNDERGOING_MSG);

            return $resultArray;
        });

        return $responseObject;
    }

    /**
     * Generic routing function to perform an action on a post
     * @param int $postHash Post ID of comment being manipulated
     * @param string $action The action to perform
     * @param object $data The data sent with the action (comment or rating)
     * @param int $commentID The comment associated with the rating
     * @return array Results from request
     */
    public function performPostCommentAction($postHash, $action, $data, $commentID)
    {
        $resultArray = array('action' => $action, 'id' => $commentID);
        $responseObject = $this->getResponseObject($resultArray, 'is_array');
        if(!Framework::isLoggedIn())
        {
            $responseObject->error = Config::getMessage(LOGGED_PERFORM_ACTIONS_MSG);
            return $responseObject;
        }
        switch(strtolower($action))
        {
            case 'reply':
                $baseUrl = $this->generateCommunityApiUrl(array('Action' => 'CommentAdd')) . "&postHash=$postHash";
                $postParameters = '&payload=<?xml version="1.0"?><comments><comment><value><![CDATA[' . urlencode($data) . ']]></value></comment></comments>';
                ActionCapture::record('communityComment', 'create', $postHash);
                break;
            case 'rate':
                $baseUrl = $commentID ? $this->generateCommunityApiUrl(array('Action' => 'CommentRate')) . "&commentId=$commentID&RatingValue=$data"
                                      : $this->generateCommunityApiUrl(array('Action' => 'PostRate')) . "&postHash=$postHash&ratingValue=$data";
                $resultArray['rating'] = intval($data);
                $responseObject->result = $resultArray;
                ActionCapture::record('communityComment', 'rate', $postHash);
                ActionCapture::record('communityComment', 'rated', $data->rating);
                break;
        }

        $response = $this->makeRequestToCommunity($baseUrl, $postParameters);
        $this->processCommunityResponse($response, 'comment', $responseObject, function($comment) use ($resultArray) {
            $resultArray['comment'] = $comment;
            if(intval($comment->status) !== COMMENT_STATUS_ACTIVE)
                $resultArray['message'] = Config::getMessage(COMMENT_COMMENT_UNDERGOING_MSG);

            return $resultArray;
        });

        if ($responseObject->result && $responseObject->result['comment']) {
            // Can't use `$this` inside a closure...
            $responseObject->result['comment']->created = Framework::formatDate(strtotime($responseObject->result['comment']->created));
        }

        return $responseObject;
    }

    /**
     * Submits a post to the community API. A valid form token must be posted in
     * order for a successful post to occur.
     * @param int $postTypeID The ID of the post type to create the post for
     * @param string $resourceHash The hash of the resource to create the post in
     * @param object $title The post title must contain id and value members
     * @param object $body The post body must contain id and value members
     * @return array Results from the request
     */
    public function submitPost($postTypeID, $resourceHash, $title, $body)
    {
        $resultArray = array();
        $responseObject = $this->getResponseObject($resultArray, 'is_array');

        if(!Framework::isValidSecurityToken($this->CI->input->post('token'), 10))
        {
            $responseObject->error = new \RightNow\Libraries\ResponseError("Invalid security token.", -1);
            return $responseObject;
        }

        if(!Framework::isLoggedIn())
        {
            $responseObject->error = Config::getMessage(LOGGED_PERFORM_ACTIONS_MSG);
            return $responseObject;
        }

        $baseUrl = $this->generateCommunityApiUrl(array('Action' => 'PostAdd')) . "&hash=$resourceHash";
        $postParameters = '&payload=<?xml version="1.0" encoding="UTF-8"?>' .
                            '<post postTypeId="' . $postTypeID . '">' .
                                '<field postTypeFieldId="' . $title->id . '">' .
                                    '<value><![CDATA[' . urlencode($title->value) . ']]></value>' .
                                '</field>' .
                                '<field postTypeFieldId="' . $body->id . '">' .
                                    '<value><![CDATA[' . urlencode($body->value) . ']]></value>' .
                                '</field>' .
                            '</post>';

        $response = $this->makeRequestToCommunity($baseUrl, $postParameters);
        $this->processCommunityResponse($response, 'post', $responseObject, function($post) use ($resultArray, $resourceHash) {
                ActionCapture::record('communityPost', 'create', $resourceHash);
                $resultArray['created'] = $post->created;
                $resultArray['status'] = $post->status;
                if(intval($post->status) !== COMMENT_STATUS_ACTIVE) //post status codes mean the same thing as comment status codes
                    $resultArray['message'] = Config::getMessage(POST_UNDERGOING_MODERATION_APPEAR_MSG);

                return $resultArray;
        });

        if (($error = $responseObject->error) && $error->errorCode === COMMUNITY_ERROR_INVALID_INPUT)
        {
            $error->externalMessage = Config::getMessage(ERR_SUBMITTING_FORM_ENTERED_VALUES_MSG);
        }

        return $responseObject;
    }

    /**
     * Creates a community user.
     * @param array $userInfo Must contain 'contactID', 'name', 'email' fields ('avatarUrl' is optional)
     * @return array Status of creation
     */
    public function createUser(array $userInfo)
    {
        if($userInfo['contactID'] && $userInfo['name'] && $userInfo['email'])
        {
            $baseUrl = $this->generateCommunityApiUrl(array(
                'Action' => 'UserCreate',
                'PermissionedAs' => 'hl.api@hivelive.com'
            ));
            $postParameters = array(
                'UserGuid' => $userInfo['contactID'],
                'UserName' => $userInfo['name'],
                'UserEmail' => $userInfo['email'],
                'UserAvatar' => $userInfo['avatarUrl'],
                'UserStatus' => 1  //hard-coded 'enabled' user-status
            );

            $response = $this->makeRequestToCommunity($baseUrl, $postParameters);
            $responseObject = $this->getResponseObject(null, 'is_null');
            $this->processCommunityResponse($response, null, $responseObject, function() {
                ActionCapture::record('communityUser', 'create');
            });

            return $responseObject;
        }
        return $this->getResponseObject(null, 'is_null', 'Invalid credential keys in userInfo array.');
    }

    /**
     * Updates a community user.
     * @param int $contactID The CP user's contact ID (community Guid)
     * @param array $userInfo Must contain a value for either 'name', 'email', or 'avatarUrl' fields
     * @return array Status of creation
     */
    public function updateUser($contactID, array $userInfo)
    {
        if($userInfo['name'] || $userInfo['email'] || $userInfo['avatarUrl'])
        {
            $baseUrl = $this->generateCommunityApiUrl(array(
                'Action' => 'UserUpdate',
                'PermissionedAs' => 'hl.api@hivelive.com'
            )) . "&UserGuid=$contactID";
            $postParameters = '<?xml version="1.0" encoding="UTF-8"?><users><user>' .
                                (($userInfo['name']) ? "<name>{$userInfo['name']}</name>" : '') .
                                (($userInfo['email']) ? "<email>{$userInfo['email']}</email>" : '') .
                                (($userInfo['avatarUrl']) ? "<avatarUrl>{$userInfo['avatarUrl']}</avatarUrl>" : '') .
                          '</user></users>';

            $response = $this->makeRequestToCommunity($baseUrl, "&payload=$postParameters");
            $responseObject = $this->getResponseObject(null, 'is_null');
            $this->processCommunityResponse($response, null, $responseObject, function() {
                ActionCapture::record('communityUser', 'update');
            });

            return $responseObject;
        }
        return $this->getResponseObject(null, 'is_null', 'Invalid credential keys in userInfo array.');
    }

    /**
     * Takes user login information and generates a authentication token to
     * use within the community.
     * @param boolean $strictMode Denotes if validation of signature should occur when building up the SSO token.
     * If set to true, if the token is invalid (no email, first/last name, etc) then null will be returned.
     * @param string $redirect Denotes the URL that should be redirected to after a successful SSO routine
     * @param int $version Signature version which to use; version 3 is the new, default version.
     * @return string The token or null on error.
     */
    public function generateSsoToken($strictMode = false, $redirect = '', $version = 3)
    {
        if(!Framework::isLoggedIn())
            return $this->getResponseObject(null, 'is_null', 'User must be logged in to generate a SSO token.');

        $profileData = (array) $this->CI->session->getProfile(true);
        if ($redirect)
            $profileData['redirectUrl'] = $redirect;

        $encodedSsoDetails = '';
        $parameters = $this->getSignatureParameters($profileData, $version);
        foreach($parameters as $key => $detail) {
            if($strictMode && ($detail === null || $detail === '' || $detail === false))
                return $this->getResponseObject(null, 'is_null', "Validation of token parameters failed. The '$key' parameter does not have a value.");
            $encodedSsoDetails .= sprintf('%s%s=%s', $encodedSsoDetails ? '&' : '', $key, $version === 3 ? $detail : urlencode($detail));
        }

        $finalToken = null;
        if($version === 3) {
            $encryptedResult = $this->encryptToken($encodedSsoDetails);
            if (empty($encryptedResult))
                return $this->getResponseObject(null, 'is_null', 'Encryption failed.');
            list($salt, $iv, $token) = $encryptedResult;
            if (empty($salt) || empty($iv) || empty($token))
                return $this->getResponseObject(null, 'is_null', 'Salt and IV generation failed.');

            list($signatureSalt, $signature) = $this->calculateApiSignature($token, true);
            if (empty($signatureSalt) || empty($signature))
                return $this->getResponseObject(null, 'is_null', 'Signature generation failed.');

            $token = base64_encode($signatureSalt . $salt . $iv . $token);
            $finalToken = sprintf('token=%s&Signature=%s&SignatureVersion=3', urlencode($token), urlencode($this->hexToBase64($signature)));
        }
        else {
            $finalToken = $encodedSsoDetails;
        }

        $finalToken = strtr(base64_encode($finalToken), array('+' => '_', '/' => '~', '=' => '*'));
        return $this->getResponseObject($finalToken, 'is_string');
    }

    /**
     * Returns an associative array of signature parameters used to generate an SSO token.
     *
     * @param array $profileData An array of profile information, usually derived from CI->session->getProfile.
     * @param int $version Signature version which to use; version 3 is the new, default version.
     * @return array Signature parameters
     */
    private function getSignatureParameters(array $profileData, $version = 3)
    {
        // Note: the order of the array keys is important here
        $parameters = array(
            'ApiKey'           => Config::getConfig(COMMUNITY_PUBLIC_KEY),
            'p_cid'            => $profileData['contactID'],
            'p_email.addr'     => $profileData['email'],
            'p_name.first'     => $profileData['firstName'],
            'p_name.last'      => $profileData['lastName'],
            'SignatureVersion' => $version
        );

        if($version !== 3) {
            list(, $signature) = $this->calculateApiSignature($parameters);
            $parameters['Signature'] = $this->hexToBase64($signature);
        }

        $parameters['p_sessionid'] = $this->CI->session->getSessionData('sessionID');
        $parameters['p_timestamp'] = time();

        if($profileData['redirectUrl'])
            $parameters['redirectUrl'] = $profileData['redirectUrl'];

        return $parameters;
    }

    /**
     * Encrypts a string using AES encryption.
     *
     * @param string $message String which to encrypt.
     * @return array An array consisting of the salt, truncated initialization vector, and token. Array is empty when something went wrong.
     */
    private function encryptToken($message)
    {
        $cryptArgs = Encryption::getApiCryptArgs('aes-128-cbc');
        $salt = Encryption::getRandomBytes(self::SSO3_KEY_SALT_LENGTH);
        $cryptArgs['initializationVector'] = Encryption::getRandomBytes(self::SSO3_IV_LENGTH);
        if(empty($salt) || empty($cryptArgs['initializationVector'])){
            return null;
        }
        $cryptArgs['input'] = $message;
        $cryptArgs['secretKey'] = $this->generateKey(Config::getConfig(COMMUNITY_PRIVATE_KEY), $salt, self::SSO3_KEY_LENGTH);
        $cryptArgs['keygenMethod'] = RSSL_KEYGEN_NONE;
        $cryptArgs['paddingMethod'] = RSSL_PAD_ZERO;
        $cryptArgs['base64Encode'] = false;
        $cryptArgs['salt'] = null;
        $encrypted = Encryption::apiEncrypt($cryptArgs);
        if(empty($encrypted[1])) {
            return array($salt, substr($cryptArgs['initializationVector'], 0, self::SSO3_IV_LENGTH), $encrypted[0]);
        }
        return array();
    }

    /**
     * Generates a secret key for encryption.
     *
     * @param string $key The key for which to generate the secret key.
     * @param string $salt The secret key's salt.
     * @param string $length Optional length to set.
     * @return string The generated secret key.
     */
    private function generateKey($key, $salt, $length = null)
    {
        $signature = $this->generateSignature($key, $salt);
        return substr($signature, 0, $length ?: strlen($signature));
    }

    /**
     * Determines whether a community API request can be made
     * based on whether the correct config settings are specified.
     * @return boolean whether a community API request can be made
     */
    private function canMakeCommunityApiRequest()
    {
        return Config::getConfig(COMMUNITY_ENABLED) && Config::getConfig(COMMUNITY_PUBLIC_KEY) !== '' && Config::getConfig(COMMUNITY_PRIVATE_KEY) !== '';
    }

    /**
     * Utility function to build up a web request to the community server
     * @param string $url The URL to make the request to
     * @param string $postString POST data to send with the request
     * @param boolean $isCacheable Indicates if the request can be satisfied with cached data and if the response is suitable for caching.
     * @return string The response from the the request
     */
    private function makeRequestToCommunity($url, $postString = '', $isCacheable = false)
    {
        if(get_class($this) === 'MockSocial')
            return \MockSocial::mockMakeRequestToCommunity($url, $postString, $isCacheable);
        $communityHostname = $this->communityHostname;
        $communityProtocol = $this->communityProtocol;
        $timeout = IS_UNITTEST ? 35 : self::REQUEST_TIMEOUT_LENGTH;
        $requester = function() use($url, $postString, $communityHostname, $communityProtocol, $timeout) {
            //Use curl if request is a POST or over SSL
            if($communityProtocol === 'https' || $postString)
            {
                if(!extension_loaded('curl') && !@Api::load_curl())
                    return null;
                $ch = curl_init();
                $options = array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_HTTPHEADER => array("Host: {$communityHostname}"),
                );
                if($postString)
                {
                    $options[CURLOPT_POST] = 1;
                    $options[CURLOPT_POSTFIELDS] = $postString;
                }
                if($communityProtocol === 'https')
                {
                    $options[CURLOPT_SSL_VERIFYHOST] = 0;
                    $options[CURLOPT_SSL_VERIFYPEER] = false;
                }
                curl_setopt_array($ch, $options);
                $response = @curl_exec($ch);
            }
            else
            {
                $context = array(
                    'http' => array(
                        'timeout' => $timeout,
                        'header' => "Host: {$communityHostname}\r\n"
                    )
                );
                $response = @file_get_contents($url, 0, stream_context_create($context));
            }
            return $response;
        };

        if($isCacheable && !$postString)
        {
            $cache = new \RightNow\Libraries\Cache\PersistentReadThroughCache(5 * 60, $requester);
            try {
                return $cache->get($url);
            }
            catch (\Exception $e) {
                //Cache attempt fails, just continue on and request live data
            }
        }
        return $requester();
    }

    /**
     * Generates a URL with the appropriate parameters to make API requests to the community.
     * @param array $signatureParameters Array of additional parameters to add to the request
     * @param array|null $restEndpoint Specifies the REST endpoint for the  community API (rather than the default Query API); must contain an 'endpoint'
     * key and optionally an 'identifier' key
     * @return string URL to use for community request
     */
    private function generateCommunityApiUrl(array $signatureParameters, $restEndpoint = null)
    {
        if($signatureParameters['PermissionedAs'])
        {
            $requestCredentials = $signatureParameters['PermissionedAs'];
            unset($signatureParameters['PermissionedAs']);
        }
        else
        {
            $requestCredentials = 'Guest';
            if(Framework::isLoggedIn())
            {
                $userID = $this->CI->session->getProfileData('contactID');
                if($userID)
                    $requestCredentials = $userID;
            }
        }
        $defaultSignatureParameters = array('ApiKey' => urlencode(Config::getConfig(COMMUNITY_PUBLIC_KEY)), 'SignatureVersion' => 2, 'version' => $this->getApiVersion(), 'PermissionedAs' => $requestCredentials);
        $signatureParameters = array_merge($defaultSignatureParameters, $signatureParameters);
        uksort($signatureParameters, 'strcasecmp');
        list(, $signature) = $this->calculateApiSignature($signatureParameters);
        $signatureParameters['Signature'] = urlencode($this->hexToBase64($signature));
        $communityIP = Config::getConfig(COMMUNITY_SERVER_IP);
        $hostName = $communityIP ?: $this->communityHostname;
        $baseUrl = "{$this->communityProtocol}://{$hostName}/api/" .
            (($restEndpoint && $restEndpoint['action'])
                ? $restEndpoint['action'] . (($restEndpoint['identifier']) ? "/{$restEndpoint['identifier']}" : '')
                : 'endpoint'
            ) . '?format=json';
        foreach($signatureParameters as $key => $val)
            $baseUrl .= "&$key=$val";
        return $baseUrl;
    }

    /**
     * Simple utility function to build up a response object given a community response from the getCommunityObject function.
     * Checks if the result has is null or has an error and creates the appropriate error messaging. Otherwise assigns the return
     * value of the ResponseObject to the result from the community.
     *
     * @param mixed $communityResult Parsed result object from community
     * @param string $noValueErrorMessage Error message to use if response from community is null
     * @return object Data from community or null if errors were encountered
     */
    private function parseCommunityResponse($communityResult, $noValueErrorMessage)
    {
        $responseObject = new \RightNow\Libraries\ResponseObject();
        if ($communityResult === null) {
            $responseObject->error = $noValueErrorMessage;
        }
        // Check for errors returned by the community, ignoring when the user is not known since we'll still get back public data, and,
        // ignoring the 'resource not found' error as that simply indicates there are no results for the specified resource/answer.
        else if ($communityResult->error && !in_array($communityResult->error->code, array(self::AUTHENTICATED_USER_ERROR, self::POST_RESOURCE_NOT_FOUND_ERROR), true)) {
            $responseObject->error = new \RightNow\Libraries\ResponseError($communityResult->error->message, $communityResult->error->code);
        }
        else {
            $responseObject->result = $communityResult;
        }
        return $responseObject;
    }

    /**
     * Uniformly handles error-checking conditions and response object result-setting.
     * @param string $result Raw data returned from #makeRequestToCommunity
     * @param string $subObject Name of a sub-object on the JSON-decoded result object
     * to pass to $callback; if omitted, $callback is called without any arguments and the
     * JSON-decoded result object is set as $responseObject's result
     * @param Libraries\ResponseObject $responseObject ResponseObject to set the error / result on
     * @param \Closure|null $callback Function to call if a successful result is received;
     * if $subObject is specified, the return value of $callback is set as the result of $responseObject
     * @return void
     */
    private function processCommunityResponse($result, $subObject, Libraries\ResponseObject $responseObject, $callback)
    {
        if ($result) {
            $object = @json_decode($result);
            $responseObject->validationFunction = null;
            if ($object === null) {
                // JSON didn't parse
                Api::phpoutlog("Received an invalid JSON response from the community API: '$result'");
                $responseObject->error = new \RightNow\Libraries\ResponseError(Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG));
            }
            else if ($object->error) {
                // Community responded with an error
                Api::phpoutlog("Received error code {$object->error->code} from the community API");
                $responseObject->error = new \RightNow\Libraries\ResponseError(Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG), $object->error->code);
            }
            else if (!$subObject) {
                // Caller doesn't care about anything in response object
                $callback();
                $responseObject->result = $object;
            }
            else if ($subObject = $object->$subObject) {
                // Caller wants a specific sub-object and the value that's
                // returned from the callback is the reponse object's result.
                $responseObject->result = $callback($subObject);
            }
        }
        else {
            // Response is null / empty string
            Api::phpoutlog("Failed to receive a response from the community API");
            $responseObject->error = new \RightNow\Libraries\ResponseError(Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG));
        }
    }

    /**
     * Returns a specified community object from the community API.
     * @param string $apiAction The community API action
     * @param array $objectKey Contains the object key's name and value
     * @param string $hiveHash The resource hash that the object is a part of
     * @param array|null $restApiCall Specifies the REST call for the community API (rather than the default Query API); must contain an 'endpoint'
     * key and optionally an 'identifier' key
     * @param boolean $isCacheableForLoggedInUser Indicates if the request can be satisfied with cached data and if the response is suitable for caching.
     * @return mixed Response from community or null if errors were encountered
     */
    private function getCommunityObject($apiAction, array $objectKey, $hiveHash = null, $restApiCall = null, $isCacheableForLoggedInUser = false)
    {
        if(!$objectKey['value'] || !$this->canMakeCommunityApiRequest())
             return null;
        $url = $this->generateCommunityApiUrl(array('Action' => $apiAction), $restApiCall) . "&{$objectKey['name']}={$objectKey['value']}" . (($hiveHash) ? "&HiveHash=$hiveHash" : '');

        $isCacheable = !Framework::isLoggedIn() || $isCacheableForLoggedInUser;
        if (Framework::isLoggedIn() && $isCacheableForLoggedInUser)
            $url .= "&c_id=" . $this->CI->session->getProfileData('contactID');

        $response = $this->makeRequestToCommunity($url, null, $isCacheable);
        return ($response) ? json_decode($response) : null;
    }

    /**
     * Returns the Community API version that is currently being used.
     * @return string The API version
     */
    private function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * Calculates the signature to use for the request.
     *
     * @param string $signatureParameters The string to use as part of the search API signature.
     * @param bool $useSignatureSalt When true, use signature salt.
     * @return array An array consisting of two values: signature salt, if used (may be null), and the signature itself.
     */
    private function calculateApiSignature($signatureParameters, $useSignatureSalt = false)
    {
        $stringToSign = '';
        if(is_array($signatureParameters)) {
            foreach($signatureParameters as $key => $val)
                $stringToSign .= "$key$val";
        }
        else {
            $stringToSign = $signatureParameters;
        }

        $signatureSalt = null;
        $secretKey = Config::getConfig(COMMUNITY_PRIVATE_KEY);
        if($useSignatureSalt) {
            $signatureSalt = Encryption::getRandomBytes(self::SSO3_SIGNATURE_SALT_LENGTH);
            if(empty($signatureSalt)){
                return null;
            }
            $secretKey = $this->generateKey(Config::getConfig(COMMUNITY_PRIVATE_KEY), substr($signatureSalt, 0, self::SSO3_SIGNATURE_SALT_LENGTH));
        }

        $signature = $this->generateSignature($stringToSign, $secretKey);
        return array($signatureSalt, $signature);
    }

    /**
     * Generate a signature.
     *
     * @param string $stringToSign The string for which to generate a signature for.
     * @param string $secretKey The secret key to use when generating the signature.
     * @return string The generated signature.
     */
    private function generateSignature($stringToSign, $secretKey)
    {
        if(strlen($secretKey) > 64)
            $secretKey = pack('H40', sha1($secretKey));
        if(strlen($secretKey) < 64)
            $secretKey = str_pad($secretKey, 64, chr(0));

        $innerPaddedKey = (substr($secretKey, 0, 64) ^ str_repeat(chr(0x36), 64));
        $outerPaddedKey = (substr($secretKey, 0, 64) ^ str_repeat(chr(0x5C), 64));
        return sha1($outerPaddedKey . pack('H40', sha1($innerPaddedKey . $stringToSign)));
    }

    /**
     * Converts the string from hex to base 64
     * @param string $string The string to convert
     * @return The converted string
     */
    private function hexToBase64($string)
    {
        $raw = '';
        $stringLength = strlen($string);
        for($i = 0; $i < $stringLength; $i += 2)
            $raw .= chr(hexdec(substr($string, $i, 2)));
        return base64_encode($raw);
    }
}
