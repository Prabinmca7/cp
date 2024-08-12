<?php

namespace RightNow\Controllers;
use RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Utils\Config,
    RightNow\Libraries\OpenLoginErrors,
    RightNow\Internal\Libraries\OpenLogin as OpenLoginLibrary,
    RightNow\Utils\Framework,
    RightNow\Api,
    RightNow\ActionCapture,
    RightNow\Connect\v1_4 as Connect;

require_once CPCORE . 'Libraries/OpenLoginErrors.php';
require_once CPCORE . 'Internal/Libraries/OpenLogin/FacebookUser.php';
require_once CPCORE . 'Internal/Libraries/OpenLogin/TwitterUser.php';
require_once CPCORE . 'Internal/Libraries/OpenLogin/GoogleUser.php';
require_once CPCORE . 'Internal/Libraries/OpenLogin/OpenIDUser.php';

/**
 * Provides facility to communicate with third-party identity providers.
 */
final class OpenLogin extends Base{
    /**
     * General
     */
    const TEMP_COOKIE_NAME = 'cp_oauth_credentials';
    const MAX_OPENID_URL_LENGTH = 255; //schema restriction
    const MAX_CONTACT_NAME_LENGTH = 80; //schema restriction

    /**
     * URLs
     */

    /**
     * First: redirect the user to authorize
     */
    const FB_AUTH_URL = 'https://graph.facebook.com/oauth/authorize';
    const GOOGLE_AUTH_URL = 'https://accounts.google.com/o/oauth2/auth';

    /**
     * Second: request an access token
     * Changes to update from deprecated accounts.google.com/o/oauth2/token to current URL
     */
    const FB_ACCESS_URL = 'https://graph.facebook.com/oauth/access_token';
    const GOOGLE_ACCESS_URL = 'https://oauth2.googleapis.com/token';

    /**
     * Third: request contact data
     * Changes to update from deprecated googleapis.com/plus/v1/people/me/openIdConnect to current URL
     */
    const FB_CONTACT_API_URL = 'https://graph.facebook.com/me?fields=id,name,email,first_name,last_name';
    const FB_POST_URL = 'https://graph.facebook.com/me/feed?';
    const FB_PROFILE_PIC_URL = 'http://graph.facebook.com/%s/picture';
    const FB_REVOKE_AUTH_URL = 'https://api.facebook.com/method/auth.revokeAuthorization';
    const GOOGLE_CONTACT_API_URL = 'https://openidconnect.googleapis.com/v1/userinfo';
    const YAHOO_DISCOVERY_URL = 'https://me.yahoo.com';

    private $oAuthCallbackUrl;
    private $openIDCallbackUrl;
    private $referrer;
    private $client;
    private $noCPLogin;

    public function __construct(){
        parent::__construct();
        // Allow full access of public methods for users who aren't logged in if CP_CONTACT_LOGIN_REQUIRED is on.
        parent::_setMethodsExemptFromContactLoginRequired(array(
            'openid',
            'oauth',
            'saml'
        ));

        $this->oAuthCallbackUrl = Url::getShortEufBaseUrl('sameAsRequest', '/ci/openlogin/oauth/callback/%s/%s');
        $this->openIDCallbackUrl = Url::getShortEufBaseUrl('sameAsRequest', '/ci/openlogin/openid/callback/%s');
        $this->referrer = $_SERVER['HTTP_REFERER']; //lerned how to spell.
        $this->client = (func_num_args() === 1) ? func_get_arg(0) : new Client;
        $this->noCPLogin = false;
    }

    /**
     * Public OpenID router method.
     * @param string $action The action being performed (authorize, callback)
     * @param string $providerNameOrUrl Either a url-encoded OpenID provider's Url
     *           or the name of a supported OpenID provider (google, yahoo)
     * @param string $redirectUrl The URL of the page to redirect back to once logged-in
     */
    public function openid($action, $providerNameOrUrl, $redirectUrl = ''){
        header('Expires: Mon, 19 May 1997 07:00:00 GMT'); // Date in the past
        $this->openIDCallbackUrl .= Url::sessionParameter() . '/';

        if(Framework::isLoggedIn()){
            $this->client->redirectBackToCPPage($this->referrer);
        }
        if($action === 'authorize'){
            if(func_num_args() > 3 && $redirectUrl !== 'session' && func_get_arg(3) !== 'session'){
                //something's fishy here. If there's more than the three parameters it's likely because the caller
                //didn't urlencode the redirect URL
                //if there's a session parameter and the session cookie hasn't been set, then we'll deal with
                //that error later on...
                exit(Config::getMessage(REDIRECT_PARAM_URL_ENCODED_CP_URL_MSG));
            }
            $this->_authorizeOpenID($providerNameOrUrl, $this->_buildCallbackUrl($redirectUrl));
        }
        else if($action === 'callback'){
            if(Text::stringContains($providerNameOrUrl, '?openid')){
                //the OpenID provider just appended a querystring onto the url
                //rather than sending GET params... *shakes fist at yahoo*
                $providerNameOrUrl = Text::getSubstringBefore($providerNameOrUrl, '?openid');
            }
            $this->_callbackOpenID($providerNameOrUrl);
        }
        else{
            exit(Config::getMessage(ACTION_PROVIDER_INCORRECT_MSG));
        }
    }

    /**
     * Public OAuth router method. In addition to action and provider, also takes redirect params.
     * @param string $action Either authorize or callback
     * @param string $providerName The OAuth provider's name. Values are [facebook, twitter].
     */
    public function oauth($action, $providerName) {
        header('Expires: Mon, 19 May 1997 07:00:00 GMT'); // Date in the past
        $this->oAuthCallbackUrl .= Url::sessionParameter() . '/';

        // We now accept fbDetailsOnly to retrieve facebook login credentials without
        // actually logging into CP as a new user
        // We will set $this->noCPLogin to true
        // We will also set $providerName as facebook, so rest of code remains unaffected
        if ($providerName === 'fbDetailsOnly') {
            $this->noCPLogin = true;
            $providerName = 'facebook';
        }

        if($this->noCPLogin === false && Framework::isLoggedIn()){
            $this->client->redirectBackToCPPage($this->referrer);
        }
        if(Framework::inArrayCaseInsensitive(array('authorize', 'callback'), $action) &&
            Framework::inArrayCaseInsensitive(array('facebook', 'twitter', 'google'), $providerName)){
            //The only entry-points are the authorize and callback actions for the OOTB supported third-party providers
            if($action === 'authorize'){
                if(func_num_args() > 3 && $redirectUrl !== 'session' && func_get_arg(3) !== 'session'){
                    //something's fishy here. If there's more than the three parameters it's likely because the caller
                    //didn't urlencode the redirect URL
                    //if there's a session parameter and the session cookie hasn't been set, then we'll deal with
                    //that error later on...
                    exit(Config::getMessage(REDIRECT_PARAM_URL_ENCODED_CP_URL_MSG));
                }
                $redirectParams = $this->_buildCallbackUrl(func_num_args() > 2 ? func_get_arg(2) : null);
            }
            else if($action === 'callback'){
                if ($providerName === 'twitter' || $providerName === 'facebook') {
                    $redirectParams = array_slice(func_get_args(), 2);
                }
                else {
                    $redirectParams = func_num_args() > 2 ? func_get_arg(2) : null;
                }
            }

            $functionToCall = "_$action" . ucfirst($providerName);
            $this->$functionToCall($redirectParams);
        }
        else{
            echo Config::getMessage(ACTION_PROVIDER_INCORRECT_MSG);
        }
    }

    /**
     * Public SAML router method.
     * Expects an optional 'subject' key value pair in the url followed by an optional key 'redirect'
     * followed by the / delimited path to redirect to. Post data keys are
     *
     *   subject The SAML subject. If specified, must be the first key/value pair.
     *         Valid values are:
     *        'login', 'email', 'id', NameOfCustomField (c$name or simply name). Defaults to 'login'
     *   redirect Path of CP page to redirect to after successful login. Defaults to home page
     *         must begin with 'app', 'ci', or 'cc' else defaults to home page
     *         example: '/app/ask'
     *
     *   Example urls would be http://sitename.com/ci/openlogin/saml/subject/email/redirect/app/answers/list
     *                       http://sitename.com/ci/openlogin/saml/redirect/app/ask
     *                       http://sitename.com/ci/openlogin/saml/redirect/ci/social/ssoRedirect
     *                       http://sitename.com/ci/openlogin/saml
     *
     * SAML Sequence Diagram: ({@link http://bit.ly/i5jsxw})
     */
    public function saml(){
        $token = $this->input->post('SAMLResponse');
        $urlParameters = Url::getParameterString();
        //If the token is not present authentication fails
        if(!$token)
            $this->client->redirectToSamlErrorUrl(OpenLoginErrors::SAML_TOKEN_REQUIRED, $urlParameters);
        $args = self::_interpretSamlArguments($this->uri->segment_array());
        if(!$args['subject']){
            $this->client->redirectToSamlErrorUrl(OpenLoginErrors::SAML_SUBJECT_INVALID, $urlParameters);
        }
        $result = $this->_loginUsingSamlToken($token, $args['subject'], $args['customFieldName'], $args['redirect']);

        if($result['success']){
            ActionCapture::record('contact', 'login', 'saml');
            $this->client->redirectBackToCPPage($args['redirect']);
        }
        $this->client->redirectToSamlErrorUrl($result['error'], $urlParameters);
    }

    /**
     * Attach an email address to contact data retrieved from an Open Login service
     * (that doesn't provide the user's email address) and continue the login process.
     * All required parameters (email, userData) must be posted to this controller entry-point.
     */
    public function provideEmail(){
        $email = $this->input->post('email');
        $encryptedUserData = $this->input->post('userData');
        if($email && $encryptedUserData){
            if(!Text::isValidEmailAddress($email)){
                exit(OpenLoginErrors::INVALID_EMAIL_ERROR);
            }
            $userData = unserialize(Api::ver_ske_decrypt($encryptedUserData));
            if($userData->id && $userData->providerName && $userData->userName && $userData->firstName && $userData->lastName){
                $userClass = "\\RightNow\\Internal\\Libraries\\OpenLogin\\" . ucfirst(strtolower($userData->providerName)) . 'User';
                $user = new $userClass($userData->providerName, null);
                $user->id = $userData->id;
                $user->userName = $userData->userName;
                $user->avatarUrl = $userData->avatarUrl;
                $user->firstName = $userData->firstName;
                $user->lastName = $userData->lastName;
                $user->email = $email;
                $success = $this->_loginUser($user, $userData->providerName);
                if($success === true)
                    echo 'true';
            }
        }
    }

    /**
     * Generic function to log out the currently logged-in account.
     * If the logout was successful and a redirect was specified in the url (e.g /ci/openlogin/logout/app/answers/list), then the user will be redirected.
     * If the specified redirect url is invalid, or is absolute and does not match PTA_EXTERNAL_POST_LOGOUT URL, the redirect will go to /app/{CP_HOME_URL}.
     */
    public function logout() {
        Url::redirectToHttpsIfNecessary();
        if(Framework::isLoggedIn()) {
            if ($this->model('Contact')->doLogout(Url::getOriginalUrl())->result && ($redirectUrl = urldecode(implode('/', array_slice($this->uri->segment_array(), 2))))) {
                Framework::setLocationHeader(($this->_validateRedirect($redirectUrl) ?: Url::getHomePage()));
            }
        }
        else {
            Framework::setLocationHeader(Url::getHomePage());
        }
        exit();
    }

    /**
     * Returns a validated redirect url if $redirectUrl parses and, is relative, or matches PTA_EXTERNAL_POST_LOGOUT_URL.
     * @param  string|null $redirectUrl Validates redirect URL and also handles relative redirect paths
     * @return string|null The valudated URL or null if value provided wasn't a valid URL
     */
    private function _validateRedirect($redirectUrl = null) {
        if (!$redirectUrl) {
            return;
        }

        $getParsed = function($url) {
            if ($parsed = @parse_url($url)) {
                $parsed['url'] = $parsed['host'] ?: $parsed['path'];
                if ($parsed['path']) {
                    $info = pathinfo($parsed['path']);
                    $parsed['dirname'] = $info['dirname'];
                }
                $parsed['isRelative'] = (!$parsed['host'] && !$parsed['scheme'] && $parsed['dirname'] !== '.' && $parsed['dirname'] !== '/' && $parsed['dirname'] !== ':');

                return $parsed;
            }
        };

        if ($parsed = $getParsed($redirectUrl)) {
            if ($parsed['isRelative']) {
                return Text::beginsWith($redirectUrl, '/') ? $redirectUrl : "/$redirectUrl";
            }
            $parsedPtaUrl = $getParsed(Config::getConfig(PTA_EXTERNAL_POST_LOGOUT_URL));
            if($parsedPtaUrl && ($parsed['url'] === $parsedPtaUrl['url'])) {
                return $redirectUrl;
            }
        }
    }

    /**
     * Make the initial request for access tokens.
     */

    /**
     * Makes the initial redirect to Google.
     * @param  string $redirectUrl The CP URL to redirect to when the OAuth dance is done
     */
    private function _authorizeGoogle($redirectUrl) {
        if (($googleAppID = Config::getConfig(GOOGLE_OAUTH_APP_ID)) && Config::getConfig(GOOGLE_OAUTH_APP_SECRET)) {
            $params = array(
                'scope'         => 'openid email profile',
                'state'         => Api::ver_ske_encrypt_urlsafe($redirectUrl),
                'client_id'     => $googleAppID,
                // redirect_uri must be identical to the registered app URL; we can't stuff additional params onto the end, like other OAuth providers.
                'redirect_uri'  => rtrim(sprintf(Url::deleteParameter($this->oAuthCallbackUrl, 'session'), 'google', ''), '/'),
                'openid.realm'  => Url::getShortEufBaseUrl('sameAsRequest'),
                'response_type' => 'code',
            );
            $url = self::GOOGLE_AUTH_URL . '?' . http_build_query($params, '', '&');
            $this->client->redirectToThirdPartyLogin($url);
        }
        else {
            exit(Config::getMessage(CFG_VALS_GOOGLE_OAUTH_APP_ID_GOOGLE_MSG));
        }
    }

    /**
     * Makes the initial redirect to Facebook.
     * @param string $redirectUrl The CP URL that Facebook redirects back to
     */
    private function _authorizeFacebook($redirectUrl){
        if(($facebookAppID = Config::getConfig(FACEBOOK_OAUTH_APP_ID)) && Config::getConfig(FACEBOOK_OAUTH_APP_SECRET)){
            //Facebook uses OAuth 2.0, which means super-simple for us -> redirect to FB's login page without first having to get a request token
            $parameters = array(
                'client_id' => $facebookAppID,
                'scope' => 'email', //request user's email address in addition to common profile info
                'redirect_uri' => sprintf($this->oAuthCallbackUrl, ($this->noCPLogin) ? 'fbDetailsOnly' : 'facebook', urldecode($redirectUrl)),
            );
            if($this->agent->supportedMobileBrowser() !== false){
                $parameters['display'] = 'touch'; //good ol' FB is the only provider that doesn't do UA detection and display the appropriate page set on their own...
            }

            $this->client->redirectToThirdPartyLogin(self::FB_AUTH_URL, $parameters);
        }
        else{
            exit(Config::getMessage(CFG_VALS_FACEBOOK_OAUTH_APP_ID_MSG));
        }
    }

    /**
     * Performs an initial GET to request temporary credentials from the Twitter API.
     * Redirects to the Twitter authorize URL.
     * @param string $redirectUrl The CP URL that Twitter redirects back to
     */
    private function _authorizeTwitter($redirectUrl){
        if(($twitterAppID = Config::getConfig(TWITTER_OAUTH_APP_ID)) && ($twitterAppSecret = Config::getConfig(TWITTER_OAUTH_APP_SECRET))){
            require_once DOCROOT . '/admin/cloud/include/oauth/oauth.php';
            //get a request token
            $consumerToken = new \OAuthConsumer($twitterAppID, $twitterAppSecret);
            $callbackUrl = sprintf($this->oAuthCallbackUrl, 'twitter', $redirectUrl);
            $oAuthRequest = \OAuthRequest::from_consumer_and_token(
                $consumerToken,
                null, //don't yet have an access token
                'GET',
                Config::getConfig(TWITTER_REQUEST_TOKEN_URL), //oauth/request_token
                array('oauth_callback' => $callbackUrl) //array of params to send
            );
            $oAuthRequest->sign_request(new \OAuthSignatureMethod_HMAC_SHA1(), $consumerToken, null);
            $authenticateUrl = $this->client->get($oAuthRequest->to_url(), null, $statusCode);
            if ($statusCode !== 200) {
                // Didn't get a request token due to a problem with the app tokens, nonce, or timestamp we sent.
                Api::phpoutlog("Failed to get an access token.\nRequested: " . $oAuthRequest->to_url() . "\nTwitter responded with: $authenticateUrl");
                $this->_returnToCPPageAfterDance($redirectUrl, OpenLoginErrors::AUTHENTICATION_ERROR);

                return;
            }
            $tempToken = \OAuthUtil::decodeUrlEncodedArray($authenticateUrl);
            //stash off oauth token, oauth token secret
            $this->client->setCookie(self::TEMP_COOKIE_NAME, Api::ver_ske_encrypt_urlsafe("oauth_token={$tempToken['oauth_token']}&oauth_token_secret={$tempToken['oauth_token_secret']}"));

            $this->client->redirectToThirdPartyLogin(Config::getConfig(TWITTER_AUTHENTICATE_URL) . '?oauth_token=' . $tempToken['oauth_token']);
        }
        else{
            exit(Config::getMessage(CFG_VALS_TWITTER_OAUTH_APP_ID_MSG));
        }
    }

    /**
     * Performs OpenID document discovery on the OpenID discovery URL and then redirects the user.
     * @param string $providerUrl Name of provide to use or URL of provider
     * @param string $redirectUrl The CP URL that the OpenID provider redirects back to
     */
    private function _authorizeOpenID($providerUrl, $redirectUrl){
        $providerUrl = trim(urldecode($providerUrl));
        if(Text::isValidUrl($providerUrl)){
            $openIDUrl = $providerUrl;
        }
        else if($providerUrl === 'google'){
            // Google used to provide OpenID authentication.
            // Now it only does OAuth, but we'll continue supporting
            // the /ci/openlogin/openid/authorize/google endpoint.
            return $this->_authorizeGoogle($redirectUrl);
        }
        else if($providerUrl === 'yahoo'){
            $openIDUrl = self::YAHOO_DISCOVERY_URL;
        }
        else{
            exit(Config::getMessage(SPECIFIED_OPENID_URL_IS_INVALID_MSG));
        }

        if($openIDUrl){
            $this->client->loadCurl();
            require_once CPCORE . 'Libraries/ThirdParty/LightOpenID.php';
            $openID = new \RightNow\Libraries\ThirdParty\LightOpenID();
            $openID->identity = $openIDUrl;
            $openID->realm = Url::getShortEufBaseUrl('sameAsRequest');
            $openID->returnUrl = sprintf($this->openIDCallbackUrl, $redirectUrl);
            $openID->required = array(
                'namePerson/first',
                'namePerson/last',
                'contact/email',
                //fallbacks in case the provider doesn't give the first & last name
                'namePerson/friendly',
                'namePerson'
            );
            try{
                $authUrl = $openID->authUrl();
                // Allow customers to tweak the authUrl
                $postHookData = array('authUrl' => $authUrl);
                \RightNow\Libraries\Hooks::callHook('post_openlogin_authurl', $postHookData);
                $this->client->redirectToThirdPartyLogin($postHookData['authUrl']);
            }
            catch(\ErrorException $invalidUrlException){
                $errorCode = $invalidUrlException->getCode();
                if($errorCode === CURLE_OPERATION_TIMEOUTED || $errorCode === CURLE_COULDNT_CONNECT){
                    $error = OpenLoginErrors::OPENID_CONNECT_ERROR;
                }
                else{
                    //the URL that the user provided isn't a valid OpenID discovery point
                    Api::phpoutlog("The URL <$providerUrl> is not a valid OpenID discovery point");
                    $error = OpenLoginErrors::OPENID_INVALID_PROVIDER_ERROR;
                }
                $this->_returnToCPPageAfterDance($redirectUrl, $error);
            }
        }
    }

    /**
     * Callbacks after the user has logged-in with their provider and granted access.
     */

    /**
     * Called from Twitter after the authorization step has taken place.
     * @param array $redirectSegments URL segment array, containing the CP page to redirect back to upon success/failure
     */
    private function _callbackTwitter(array $redirectSegments){
        $redirectUrl = implode('/', $redirectSegments);
        $oAuthVerifier = $_REQUEST['oauth_verifier'];
        $tempTokens = $_COOKIE[self::TEMP_COOKIE_NAME];
        if($oAuthVerifier && $tempTokens){
            //hooray: the user authorized us! now get an access token
            Framework::destroyCookie(self::TEMP_COOKIE_NAME);
            require_once DOCROOT . '/admin/cloud/include/oauth/oauth.php';

            $tempTokens = \OAuthUtil::decodeUrlEncodedArray(Api::ver_ske_decrypt($tempTokens));

            if(is_array($tempTokens)){
                $consumerToken = new \OAuthConsumer(Config::getConfig(TWITTER_OAUTH_APP_ID), Config::getConfig(TWITTER_OAUTH_APP_SECRET));
                $accessToken = new \OAuthConsumer($tempTokens['oauth_token'], $tempTokens['oauth_token_secret']);
                $oAuthRequest = \OAuthRequest::from_consumer_and_token(
                    $consumerToken,
                    $accessToken,
                    'GET',
                    Config::getConfig(TWITTER_ACCESS_TOKEN_URL), //oauth_access_token
                    array('oauth_verifier' => $oAuthVerifier) //array of params to send
                );
                $oAuthRequest->sign_request(new \OAuthSignatureMethod_HMAC_SHA1(), $consumerToken, null);

                $response = \OAuthUtil::decodeUrlEncodedArray($this->client->get($oAuthRequest->to_url()));
                if($response['user_id']){
                    $user = $this->_getTwitterUserInfo($response);
                    if($user->id){
                        $userIsLoggedIn = $this->_loginUser($user, 'twitter');
                        if($userIsLoggedIn !== true){
                            if(is_string($userIsLoggedIn)){
                                //need to redirect back... user needs to verify email
                                $emailError = $userIsLoggedIn;
                            }
                            else{
                                $error = $userIsLoggedIn;
                            }
                        }
                    }
                    else if($user->error){
                        Api::phpoutlog("Twitter user authentication failed. Twitter error message: {$user->error}");
                        $error = OpenLoginErrors::TWITTER_API_ERROR;
                    }
                }
                else{
                    //authentication problem on our part...
                    Api::phpoutlog("Twitter user authentication failed. Twitter responded with $response");
                    $error = OpenLoginErrors::AUTHENTICATION_ERROR;
                }
            }
            else {
                Api::phpoutlog("The value found in the " . self::TEMP_COOKIE_NAME . " cookie was invalid");
            }
        }
        else if(!$oAuthVerifier){
            //twitter oauth error
            Api::phpoutlog("Twitter did not send an oauth_verifier in the request");
            $error = OpenLoginErrors::AUTHENTICATION_ERROR;
        }
        else if(!$tempTokens){
            //cookies disabled
            Api::phpoutlog("A required cookie named " . self::TEMP_COOKIE_NAME . " was not found");
            $error = OpenLoginErrors::COOKIES_REQUIRED_ERROR;
        }
        $this->_returnToCPPageAfterDance($redirectUrl, $error, $emailError);
    }

    /**
     * Called from Facebook after the authorization step has taken place.
     * @param array $redirectSegments Contains segments of
     *   The page to ultimately redirect to upon a successful login (optional)
     *   /onfail/ The original requesting page to go back to if there's a failure or if no redirect page specified
     *   either ?code=... (the request token) or ?error_reason=... (String error sentence)
     */
    private function _callbackFacebook(array $redirectSegments) {
        // To get around FB's restrictions the redirect URL must be a URL segment as opposed to simply a urlencoded URL...
        $redirectUrl = implode('/', $redirectSegments);
        $redirectUrl = Text::getSubStringBefore($redirectUrl, '/?code=', $redirectUrl);
        $requestToken = $_REQUEST['code'];

        if($requestToken){
            //get the FB access token
            $response = $this->client->post(self::FB_ACCESS_URL, array(
                'code' => $requestToken,
                'client_id' => Config::getConfig(FACEBOOK_OAUTH_APP_ID),
                //The redirect_uri must be identical to the one specified in the request process otherwise the
                //token that's returned isn't valid.
                'redirect_uri' => sprintf(Url::deleteParameter($this->oAuthCallbackUrl, 'session'), ($this->noCPLogin) ? 'fbDetailsOnly' : 'facebook', $redirectUrl),
                'client_secret' => Config::getConfig(FACEBOOK_OAUTH_APP_SECRET)
            ));

            //get access token from JSON response
            $result = @json_decode($response);
            $accessToken = (json_last_error() === JSON_ERROR_NONE) ? $result->access_token : null;
            //get user info from Facebook API
            $user = $this->_getFacebookUserInfo($accessToken);

            if($user->id){
                // set flash data to let page know, user logged into facebook for 1st time
                $this->session->setFlashData(array('fbFirstLogin' => true));

                if($this->noCPLogin) {
                    // put access token in session data to be made available by Models
                    // for re-validation of logged in CP-user's facebook credentails
                    $this->session->setSessionData(array('fbToken' => $accessToken));
                    $this->_returnToCPPageAfterDance(urlencode($redirectUrl));

                    return;
                }

                //login the user (implicit contact creation may occur)
                $userIsLoggedIn = $this->_loginUser($user, 'facebook');
                if($userIsLoggedIn !== true){
                    if(is_string($userIsLoggedIn)){
                        //need to redirect back... user needs to verify email
                        $emailError = $userIsLoggedIn;
                    }
                    else{
                        $error = $userIsLoggedIn;
                        if($error === OpenLoginErrors::FACEBOOK_PROXY_EMAIL_ERROR){
                            Api::phpoutlog("User provided Facebook proxy email, which is invalid; revoking app's authorization so user can re-attempt to authenticate and supply a valid email");
                            $this->_revokeFacebookAuthorization($accessToken);
                        }
                    }
                }
            }
            else{
                //authentication problem on our part...
                Api::phpoutlog("Facebook user authentication failed. Facebook error message: {$user->error}");
                $error = OpenLoginErrors::AUTHENTICATION_ERROR;
            }
        }
        else if($_REQUEST['error_reason']){
            //we feel rejected by the user :'(
            Api::phpoutlog("Facebook user authentication failed. The user did not allow the Facebook app access to basic profile info");
            $error = OpenLoginErrors::USER_REJECTION_ERROR;
        }
        $this->_returnToCPPageAfterDance(urlencode($redirectUrl), $error, $emailError);
    }

    /**
    * Called from Google after the authorization step has taken place.
    * Uses the granted request token to request the user's profile data,
    * save the user, and log them in.
    */
    private function _callbackGoogle() {
        $requestToken = $_REQUEST['code'];
        $redirectUrl = API::ver_ske_decrypt($_REQUEST['state']);

        if ($requestToken) {
            $params = array(
                'code'          => $requestToken,
                'client_id'     => Config::getConfig(GOOGLE_OAUTH_APP_ID),
                'grant_type'    => 'authorization_code',
                'client_secret' => Config::getConfig(GOOGLE_OAUTH_APP_SECRET),
                'redirect_uri'  => rtrim(sprintf(Url::deleteParameter($this->oAuthCallbackUrl, 'session'), 'google', ''), '/'),
            );

            $response = $this->client->post(self::GOOGLE_ACCESS_URL, $params);
            $response = @json_decode($response);

            if (is_object($response)) {
                $user = $this->_getGoogleUserInfo("{$response->token_type} {$response->access_token}");
            }
            else {
                Api::phpoutlog("Google access token request failed");
            }
        }
        else {
            Api::phpoutlog("Google callback failed to receive a `code` parameter");
        }

        if ($user && $user->id) {
            $userIsLoggedIn = $this->_loginOpenIDUser($user, $user->openIDUrl);
            if ($userIsLoggedIn !== true) {
                $error = $userIsLoggedIn;
            }
        }
        else {
            Api::phpoutlog("Google authentication failed. The user did not provide basic profile info");
            $error = OpenLoginErrors::AUTHENTICATION_ERROR;
        }
        $this->_returnToCPPageAfterDance($redirectUrl ?: ("/onfail/app/" . Config::getConfig(CP_LOGIN_URL)), $error);
    }

    /**
    * Called from the OpenID provider after the authorization step has taken place.
    * @param string $redirectUrl URL encoded string containing the CP page to redirect back to upon success/failure
    */
    private function _callbackOpenID($redirectUrl) {
        if ($redirectUrl === 'google') {
            return $this->_callbackGoogle();
        }

        if($_REQUEST['openid_mode'] !== 'cancel'){
            $this->client->loadCurl();
            require_once CPCORE . 'Libraries/ThirdParty/LightOpenID.php';
            if(Url::isRequestHttps()){
                //LightOpenID uses this server entry to validate the
                //openid callback, but it's ommitted in our environment...
                $_SERVER['HTTPS'] = true;
            }
            $openID = new \RightNow\Libraries\ThirdParty\LightOpenID();
            try{
                if($openID->validate()){
                    $openIDUrl = $_REQUEST['openid_identity'] ?: $_REQUEST['claimed_id'];
                    $this->_validateAndConformOpenIDUrl($openIDUrl);
                    $user = $this->_getOpenIDUserInfo($openID, $openIDUrl);
                    if($user->error){
                        Api::phpoutlog("OpenID authentication failed. The user did not provide basic profile info");
                        $error = OpenLoginErrors::OPENID_RESPONSE_INSUFFICIENT_DATA_ERROR;
                    }
                    else if(($userIsLoggedIn = $this->_loginOpenIDUser($user, $openIDUrl)) !== true){
                        $error = $userIsLoggedIn;
                    }
                }
                else{
                    Api::phpoutlog("OpenID authentication failed. The OpenID server response was not valid");
                    $error = OpenLoginErrors::AUTHENTICATION_ERROR;
                }
            }
            catch(\ErrorException $unexpectedError){
                Api::phpoutlog("OpenID authentication failed with message: " . $unexpectedError->getMessage());
                $error = OpenLoginErrors::AUTHENTICATION_ERROR;
            }
        }
        else{
            Api::phpoutlog("OpenID user authentication failed. The user cancelled the OpenID process");
            $error = OpenLoginErrors::USER_REJECTION_ERROR;
        }
        $this->_returnToCPPageAfterDance(urlencode($redirectUrl), $error);
    }

    /**
    * Get contact fields from third-party APIs
    */

    /**
     * Retrieves the user's info from Twitter's API.
     * ({@link https://dev.twitter.com/docs/api/1.1/get/users/show})
     * @param array $oAuthResponse The response data from our access token request.
     * Expects four items:
     *
     * * oauth_token: string
     * * oauth_token_secret: string
     * * user_id: string
     * * screen_name: string
     *
     * @return OpenLoginLibrary\User Object containing the user's profile info
     */
    private function _getTwitterUserInfo(array $oAuthResponse){
        // Twitter's v1.1 API requires an Authorization header,
        // so we have to create all of the OAuth params and
        // send them along in a header.
        // More info here - https://dev.twitter.com/docs/auth/creating-signature
        // v1.0 simply accepts and ignores the params and header.
        $consumerToken = new \OAuthConsumer(Config::getConfig(TWITTER_OAUTH_APP_ID), Config::getConfig(TWITTER_OAUTH_APP_SECRET));
        $accessToken = new \OAuthConsumer($oAuthResponse['oauth_token'], $oAuthResponse['oauth_token_secret']);
        $oAuthRequest = \OAuthRequest::from_consumer_and_token(
            $consumerToken,
            $accessToken,
            'GET',
            trim(Config::getConfig(TWITTER_API_URL), ' /') . '/account/verify_credentials.json',
            array('include_entities' => "false", 'skip_status' => "true", 'include_email' => "true") //array of params to send
        );
        $oAuthRequest->sign_request(new \OAuthSignatureMethod_HMAC_SHA1(), $consumerToken, $accessToken);
        // This is an object containing the user's id, screen name, name, and other info
        $userInfo = json_decode($this->client->get($oAuthRequest->to_url(), array('Authorization: OAuth ' . $oAuthRequest->to_header()), $statusCode));

        return new OpenLoginLibrary\TwitterUser($userInfo);
    }

    /**
     * Retrieves the user's info from Facebook's API.
     * @param string $accessToken A valid Facebook access token
     * @return object Object containing the user's profile info and email.
     */
    private function _getFacebookUserInfo($accessToken){
        //This is an object containing the user's id, firstName, lastName, email, and other info
        $userInfo = json_decode($this->client->get(self::FB_CONTACT_API_URL . '&access_token=' . $accessToken));

        return new OpenLoginLibrary\FacebookUser($userInfo);
    }

    /**
     * Retrieves the user's info from Google's API.
     * @param  string $accessToken A valid Google access token
     * @return object OpenLoginLibrary\User containing the user's profile info and email
     */
    private function _getGoogleUserInfo($accessToken) {
        $userInfo = json_decode($this->client->get(self::GOOGLE_CONTACT_API_URL, array(
            "Authorization: {$accessToken}",
        )));

        return new OpenLoginLibrary\GoogleUser($userInfo);
    }

    /**
     * Retrieves the user's info from provider's OpenID response.
     * @param object $openIDObject A LightOpenID object.
     * @param string $openIDUrl OpenID URL of the user.
     * @return object Object with firstName, lastName, and email members.
     */
    private function _getOpenIDUserInfo($openIDObject, $openIDUrl){
        // Allow customers to process user information from the OpenID response
        $preHookData = array('openIDObject' => $openIDObject, 'attributes' => ($openIDObject->getAttributes() + array('openIDUrl' => $openIDUrl)));
        \RightNow\Libraries\Hooks::callHook('pre_openlogin_decode', $preHookData);
        return new OpenLoginLibrary\OpenIDUser($preHookData['attributes']);
    }

    /**
     * Endpoint for Facebook's Registration Tool ({@link http://www.facebook.com/about/login})
     * Maps named values onto a Contact and either creates or updates the Contact.
     * @internal
     */
    public function facebookRegistration(){
        if($this->input->post('signed_request')){
            $response = $this->_parseSignedFacebookRequest($this->input->post('signed_request'));
            if(($userInfo = $response->registration) && $userInfo->email && $response->user_id){
                if ($userInfo->name) {
                    list($userInfo->first_name, $userInfo->last_name) = explode(' ', $userInfo->name, 2);
                }

                $fields = array();
                foreach($userInfo as $key => $value){
                    // if dealing with passwords, make sure we use the right key
                    if (strtolower($key) === "password") $key = "password_new";

                    $fieldName = "contacts.$key";
                    $connectKey = \RightNow\Utils\Connect::mapOldFieldName($fieldName);

                    // if the mapping above worked, the field name will be capitalized vs the key name that comes from v2 and is lowercase
                    if (Text::getSubstringAfter($connectKey, "Contact.") !== $key){
                        $fields[$connectKey] = (object) array('value' => $value);
                    }
                }
                $fields['Contact.ChannelUsernames.FACEBOOK.Username'] = (object) array('value' => $userInfo->login ?: $userInfo->email);
                $fields['Contact.ChannelUsernames.FACEBOOK.UserNumber'] = (object) array('value' => $response->user_id);

                if($existingContactID = $this->model('Contact')->lookupContactByEmail($userInfo->email, $userInfo->first_name, $userInfo->last_name)->result){
                    $contactResponse = $this->model('Contact')->update($existingContactID, $fields, true);
                }
                else{
                    $contactResponse = $this->model('Contact')->create($fields, false, true);
                }

                if($contactResponse->error) {
                    Api::phpoutlog("FacebookRegistration contact update|create returned error: {$contactResponse->error}");
                }

                if($contact = $contactResponse->result){
                    try {
                        $this->_doLogin($contact, 'facebook', array('thirdPartyUserID' => $response->user_id));
                    }
                    catch (\Exception $e) {
                        Api::phpoutlog("FacebookRegistration _doLogin returned error: {$e->getMessage()}");
                    }
                }
            }
        }
        if(count($redirect = func_get_args()) > 0){
            $redirect = implode('/', $redirect);
            if(Text::beginsWith($redirect, 'app/') || Text::beginsWith($redirect, 'ci/') || Text::beginsWith($redirect, 'cc/')){
                $redirect = "/$redirect";
            }
        }
        else{
            $redirect = '/app/' . Config::getConfig(CP_HOME_URL);
        }
        Framework::runSqlMailCommitHook();
        Framework::setLocationHeader($redirect);
        exit;
    }

    /**
     * Helpers
     */

    /**
     * Makes a request to Facebook's API in order to revoke the app's authorization from the user.
     * @param string $accessToken A valid Facebook access token
     */
    private function _revokeFacebookAuthorization($accessToken){
        $this->client->get(self::FB_REVOKE_AUTH_URL . '?' . $accessToken);
    }

    /**
     * Parses the specified data from a Facebook register request.
     * @param string $signedRequest Request data
     * @return object|null Object Response or null if erroneous data was given
     */
    private function _parseSignedFacebookRequest($signedRequest){
        if($facebookAppSecret = Config::getConfig(FACEBOOK_OAUTH_APP_SECRET)){
            list($encodedSignature, $payload) = explode('.', $signedRequest, 2);

            //decode the data
            $decode = function($input){
                //this form of base64 uses two different characters and doesn't have padding
                return base64_decode(strtr($input, '-_', '+/'));
            };
            $signature = $decode($encodedSignature);
            $data = @json_decode($decode($payload));

            if($data){
                if(strtoupper($data->algorithm) !== 'HMAC-SHA256') {
                    //Unknown algorithm. Expected HMAC-SHA256
                    return;
                }
                //This is where the signature should be verified, but since there's
                //no sha256 hashing function available... bail if(hash_hmac('sha256', $payload, $facebookAppSecret, true) !== $signature)
                return $data;
            }
        }
    }

    /**
     * Processes user data retrieved from a third-party authentication service:
     * Creates a new contact if one with the given email doesn't exist.
     * Updates an existing contact if:
     *   -There's no OpenLogin record for the contact
     *   -There's already an existing OpenLogin record for the contact but the
     *       OpenLogin record is incomplete (previously existed for a cloud monitor contact)
     *   -There's already an existing OpenLogin record for the contact but the
     *       contact.email no longer matches what the provider now gives us
     *   -There's already an existing OpenLogin record for the contact but the
     *       contact.login has been cleared out since the user last logged-in
     * Logs a valid user in.
     * Returns
     * @param OpenLoginLibrary\User $user User
     *    If it does not contain an email address, a String email error code is returned.
     * @param string $providerName Name of provider
     * @return string|boolean
     *  - True if the contact is successfully logged in;
     *  - False if an unknown API error occurred;
     *  - String error code if the user's email is invalid;
     *  - String Encrypted serialized user object if user data is provided but an email isn't provided.
     */
    private function _loginUser(OpenLoginLibrary\User $user, $providerName) {
        $contact = null;

        if (!$user->email) {
            if ($user->id && $user->userName) {
                $contact = $this->model('Contact')->lookupContactByOpenLoginAccount($providerName, $user->id, $user->userName)->result;
            }
            if (!$contact || !$contact->Emails[0]->Address) {
                return $this->_handleInvalidEmail($user, $providerName);
            }
        }
        else if (!Text::isValidEmailAddress($user->email)) {
            return $this->_handleInvalidEmail($user, $providerName);
        }

        // userName may be null depending on whether we have a user name from the service;
        // if it is non-null then it's applied towards looking the user up.
        // In any case, the user's id is the primary identifier for looking the user up.
        $contact = $contact ?: $this->model('Contact')->lookupContactByOpenLoginAccount($providerName, $user->id, $user->userName)->result;

        if ($contact) {
            // Pre-existing OpenLogin Contact
            $updatedContactData = $this->_updateContactFields($contact, $user, $providerName);
        }
        else if ($existingContactID = $this->model('Contact')->lookupContactByEmail($user->email, $user->firstName, $user->lastName)->result) {
            // Pre-existing Contact who hasn't used OpenLogin before
            $contact = $this->model('Contact')->get($existingContactID)->result;
            // Add a new open login record for this contact
            $updatedContactData = $this->_updateContactFields($contact, $user, $providerName);
        }
        else {
            // New Contact
            $contact = $this->model('Contact')->create($user->toContactArray(), false, true);
            if ($contact->result) {
                $newContact = true;
                $contact = $contact->result;
            }
            else {
                Api::phpoutlog("Failed to create a new contact. API responded with error message: {$contact->error}");
                $contact = null;
            }
        }

        if ($contact) {
            if (Config::getConfig(COMMUNITY_ENABLED, 'RNW')) {
                $this->_handleCommunityIntegration($contact->ID, $newContact, $user);
            }

            if ($updatedContactData) {
                $this->model('Contact')->update($contact->ID, $updatedContactData, true);
            }

            $loginResult = $this->_doLogin($contact, $providerName, array('thirdPartyUserID' => $user->id));

            if($loginResult) {
                $this->_updateSocialUser($contact, $user);
            }

            return $loginResult;
        }

        return false;
    }

    /**
     * Processes user data retrieved from a OpenID authentication service:
     * Creates a new contact if one with the given email doesn't exist.
     * Updates an existing contact if:
     *   -There's no OpenID record for the contact
     *   -There's already an existing OpenID record for the contact but the
     *       contact.email no longer matches what the provider now gives us
     *   -There's already an existing OpenID record for the contact but the
     *       contact.login has been cleared out since the user last logged-in
     * Logs a valid user in.
     * @param OpenLoginLibrary\User $user Must contain firstName, lastName, email members.
     *    If it does not contain an email address, the email error code is returned.
     * @param string $openIDUrl OpenID url that is unique to the contact
     * @return bool|string True if the contact is successfully logged in;
     *         False if an unknown API error occurred;
     *         String error code if the user's email is invalid;
     *         String Encrypted serialized user object if user data is provided but an email isn't provided.
     */
    private function _loginOpenIDUser(OpenLoginLibrary\User $user, $openIDUrl){
        //even if user does not have a valid openid url, allow to move forward
        $this->_validateAndConformOpenIDUrl($openIDUrl);

        if(!$user->email || !Text::isValidEmailAddress($user->email)){
            return OpenLoginErrors::OPENID_RESPONSE_INSUFFICIENT_DATA_ERROR;
        }

        $contact = null;

        $existingOpenIDAccount = $this->model('Contact')->lookupContactByOpenLoginAccount('openid', $openIDUrl)->result;
        // Allow customers to manage the user before they are logged in
        $preHookData = array('existingOpenIDAccount' => $existingOpenIDAccount, 'user' => $user);
        \RightNow\Libraries\Hooks::callHook('pre_openlogin_lookup_contact', $preHookData);
        $existingOpenIDAccount = $preHookData['existingOpenIDAccount'];
        $user = $preHookData['user'];

        if ($existingOpenIDAccount) {
            // Pre-existing OpenLogin Contact
            $contact = $this->model('Contact')->get($existingOpenIDAccount->ID)->result;
            $updatedContactData = $this->_updateContactFields($contact, $user, 'openid');
        }
        else if ($existingContactID = $this->model('Contact')->lookupContactByEmail($user->email, $user->firstName, $user->lastName)->result) {
            // Pre-existing Contact who hasn't used OpenLogin before or who does not have a google plus account
            $contact = $this->model('Contact')->get($existingContactID)->result;
            $updatedContactData = $this->_updateContactFields($contact, $user, 'openid');
        }
        else {
            // New Contact
            $contact = $this->model('Contact')->create($user->toContactArray(), false, true)->result;
        }

        if ($updatedContactData) {
            $contact = $this->model('Contact')->update($contact->ID, $updatedContactData, true)->result;
        }

        if ($contact) {
            $loginResult = $this->_doLogin($contact, 'openID');

            if($loginResult) {
                $this->_updateSocialUser($contact, $user);
            }

            return $loginResult;
        }
        else {
            return OpenLoginErrors::CONTACT_LOGIN_ERROR;
        }

        return false;
    }

    /**
     * Makes sure that the openid_identity URL is valid (admittedly a minimal validity check).
     * Strips whitespace and forward slashes from the URL.
     * @param string &$url The URL given as the request's openid_identity / openid_claimed_id
     * @return boolean Whether or not the URL is valid
     */
    private function _validateAndConformOpenIDUrl(&$url){
        $url = trim($url, ' /');
        return (Text::isValidUrl($url) && Text::getMultibyteStringLength($url) <= self::MAX_OPENID_URL_LENGTH);
    }

    /**
     * Makes requests to the companion community site in order to create a community user
     * when a OAuth contact logs in.
     * @param int $contactID ID of the contact
     * @param boolean $newContact Whether the contact was just newly-created
     * @param OpenLoginLibrary\User $user OpenLoginLibrary\User object containing firstName, lastName properties
     */
    private function _handleCommunityIntegration($contactID, $newContact, OpenLoginLibrary\User $user) {
        if($newContact || !($existingCommunityUser = $this->model('Social')->getCommunityUser(array('contactID' => $contactID))->result) ||
            ($existingCommunityUser->error && $existingCommunityUser->error->code === COMMUNITY_ERROR_NO_EXISTING_USER)){
            $userCreated = $this->model('Social')->createUser(array(
                'contactID' => $contactID,
                'name' => $user->name,
                'email' => $user->email,
                'avatarUrl' => $user->avatarUrl,
            ))->result;
            if($userCreated->error && $userCreated->error->code === COMMUNITY_ERROR_NON_UNIQUE_NAME){
                $retryName = $user->name . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9);
                Api::phpoutlog("Attempted to create a new Community user, but a user with the name, '{$user->name}' already exists; Trying again with '{$retryName}'");
                //collision on community user's name.
                //that means a user with a name something like "John Smith" already exists
                //so tack a four-digit sequence onto the end
                //NOTE: there's an impending community project to remove the unique name
                //requirement, thereby removing the need for this additional step and alleviating
                //concerns that yet another collision would occur on this call.
                $this->model('Social')->createUser(array(
                    'contactID' => $contactID,
                    'name' => $retryName,
                    'email' => $user->email,
                    'avatarUrl' => $user->avatarUrl,
                ))->result;
            }
        }
        else if($existingCommunityUser->user->email !== $user->email){
            $this->model('Social')->updateUser($contactID, array('email' => $user->email));
        }
    }

    /**
     * Deals with email errors for OAuth users.
     * @param  OpenLoginLibrary\User $user         OpenLoginLibrary\User object
     * @param  string                $providerName Either twitter or facebook
     * @return string|array          String if an error condition is met, an
     * array--possibly containing contact fields to update--if the user
     * with the given id already exists (twitter)
     */
    private function _handleInvalidEmail(OpenLoginLibrary\User $user, $providerName) {
        // Email problem: either no email provided or invalid email provided
        if(!$user->email){
            //the user didn't allow us their email. we need their email.
            return Api::ver_ske_encrypt_urlsafe(serialize((object) array(
                    'firstName' => $user->firstName,
                    'lastName' => $user->lastName,
                    'userName' => $user->userName,
                    'avatarUrl' => $user->avatarUrl,
                    'id' => $user->id,
                    'providerName' => $providerName,
                )
            ));
        }
        else{
            //invalid email provided
            if($providerName === 'facebook' && Text::stringContains($user->email, 'proxymail')){
                //Facebook's proxy email feature
                return OpenLoginErrors::FACEBOOK_PROXY_EMAIL_ERROR;
            }

            return OpenLoginErrors::INVALID_EMAIL_ERROR;
        }
    }

    /**
     * Updates several contact fields depending on what they're currently set at and what user info is provided.
     *   -Updates email if a different, valid, non-existent email is provided.
     *   -Updates login to the email address if the login is blank.
     *   -Updates the first and last names if they're blank and a first and last name is provided.
     * @param Connect\Contact $contact Reference of a Contact object
     * @param OpenLoginLibrary\User $user OpenLoginLibrary\User data provided by a service
     * @param string $providerName Name of the service (either twitter, facebook, openid)
     * @return array Containing updates to save or empty if nothing is to be updated
     */
    private function _updateContactFields(Connect\Contact $contact, OpenLoginLibrary\User $user, $providerName) {
        $toUpdate = array();
        if($contact->Emails[0]->Address !== $user->email && Text::isValidEmailAddress($user->email) &&
            !($someoneAlreadyHasThisEmail = $this->model('Contact')->lookupContactByEmail($user->email)->result)){
            //update the contact's email to what the third-party provider is now giving us
            //(if a different contact doesn't already have their new email address)
            if($contact->Disabled === true && $contact->Emails[0]->Address === null){
                //only enable a disabled contact if it had been disabled due to not having an email address
                $toUpdate['Contact.Disabled'] = (object) array('value' => false);
            }
            $toUpdate['Contact.Emails.PRIMARY.Address'] = (object) array('value' => $user->email);
        }
        if($contact->Login === null){
            //update the contact's login if it was cleared out since the last time they logged-in
            $toUpdate['Contact.Login'] = (object) array('value' => $user->email);
        }
        if($contact->Name->First === null && $user->firstName){
            $toUpdate['Contact.Name.First'] = (object) array('value' => $user->firstName);
        }
        if($contact->Name->Last === null && $user->lastName){
            $toUpdate['Contact.Name.Last'] = (object) array('value' => $user->lastName);
        }

        if($providerName !== 'openid'){
            $toUpdate += $user->serviceSpecificFields();
        }
        else{
            $openIDAccountFound = false;

            // if OpenID accounts already exist, verify that the OpenID URL doesn't already exist
            if($contact->OpenIDAccounts){
                foreach ($contact->OpenIDAccounts as $openIDAccount){
                    if($openIDAccount->URL === $user->openIDUrl){
                        $openIDAccountFound = true;
                        break;
                    }
                }
            }

            if(!$openIDAccountFound){
                $toUpdate += $user->serviceSpecificFields();
            }
        }

        return $toUpdate;
    }

    /**
     * Updates (or creates) a CommunityUser for the given Contact
     * with the given data on the user
     * @param  Connect\Contact       $contact Contact associated w/ CommunityUser
     * @param  OpenLoginLibrary\User $user    Third party user
     * @return Connect\CommunityUser    Social user that was updated / created
     */
    private function _updateSocialUser(Connect\Contact $contact, OpenLoginLibrary\User $user) {
        $socialUser = $this->model('CommunityUser')->getForContact($contact->ID)->result;
        $socialData = $user->toSocialUserArray();

        // Social user exists, see if we are able to update any social data
        if ($socialUser) {
            // If the user already has the field set, or the field isn't available, don't attempt to update it
            if($socialUser->DisplayName || !$socialData['Communityuser.DisplayName']->value) {
                unset($socialData['Communityuser.DisplayName']);
            }
            if($socialUser->AvatarURL || !$socialData['Communityuser.AvatarURL']->value) {
                unset($socialData['Communityuser.AvatarURL']);
            }

            $response = empty($socialData) ? $socialUser : $this->model('CommunityUser')->update($socialUser->ID, $socialData, true, true)->result;
        }
        // Social user does not exist. If a DisplayName is provided, create a Social user, otherwise, skip creation since a DisplayName is required.
        else if ($socialData['Communityuser.DisplayName']->value) {
            $formData = $user->toContactArray();

            $formData['Communityuser.Contact'] = (object) array("value" => $contact->ID);
            $formData['Communityuser.DisplayName'] = $socialData['Communityuser.DisplayName'];

            if ($socialData['Communityuser.AvatarURL']->value) {
                $formData['Communityuser.AvatarURL'] = $socialData['Communityuser.AvatarURL'];
            }

            $response = $this->model('CommunityUser')->create($formData, true)->result;
        }

        if ($response) {
            $profile = $this->session->getProfile(true);

            if($profile) {
                $socialUserID = $response->ID;
                $profile->socialUserID = $socialUserID;
                $this->session->createProfileCookie($profile);
            }

            return $response;
        }

        return false;
    }

    /**
     * Logs the contact in and sets the openLoginUsed member on the session.
     * @param Connect\Contact $contact Connect Contact object that's properly inflated w/ valid contact data
     * @param string $provider The name of the provider
     * @param array $details Optional additional details about the user:
     *                       - thirdPartyUserID: Number The user's id on the third-party service
     *                       - providerDomain: String Domain name of the provider being used. Only provided when logging in via OpenID
     * @return Boolean|Number Bool true if the operation was successful or an Int error code if the contact is disabled.
     */
    private function _doLogin(Connect\Contact $contact, $provider, array $details = array()) {
        $preHookData = array('data' => array('source' => 'OPENLOGIN'));
        \RightNow\Libraries\Hooks::callHook('pre_login', $preHookData);

        $apiProfile = (object) Api::contact_federated_login(array(
            'login' => $contact->Login,
            'sessionid' => $this->session->getSessionData('sessionID'),
            'login_method' => CP_LOGIN_METHOD_OPENLOGIN,
        ));
        if(!get_object_vars($apiProfile)) {
            Api::phpoutlog("Failed to log in contact (ID = {$contact->ID})");

            return OpenLoginErrors::CONTACT_LOGIN_ERROR;
        }

        ActionCapture::record('contact', 'login', 'openlogin');
        ActionCapture::record('openlogin', 'authenticate', substr($details['providerDomain'] ?: $provider, 0, ActionCapture::OBJECT_MAX_LENGTH));

        if($apiProfile->disabled){
            Api::phpoutlog("Failed to log in because the contact (ID = {$contact->ID}) is disabled");

            return OpenLoginErrors::CONTACT_DISABLED_ERROR;
        }

        $apiProfile->openLoginUsed = array('provider' => $provider);
        if ($userID = $details['thirdPartyUserID']) {
            $apiProfile->openLoginUsed['userID'] = $userID;
        }

        $profile = $this->session->createMapping($apiProfile);

        $postHookData = array('returnValue' => $profile, 'data' => array('source' => 'OPENLOGIN'));
        \RightNow\Libraries\Hooks::callHook('post_login', $postHookData);

        $this->session->createProfileCookie($profile);

        return true;
    }

    /**
     * Builds up a callback redirect url to send along with the user when redirecting to the third-party service.
     * If a session cookie isn't read, then either the user has cookies disabled or the authorize entry-point is
     * attempting to be called from somewhere outside of CP. Either way, that's an error and there's no
     * point in doing anything more; if this error is encountered redirect back with a cookie required message.
     * @param string $redirectUrl CP url to redirect to after a successful login; if left unspecified
     *       the referrer page is redirected back to after a successful login
     * @return string Url encoded string that consists of app/successPagePath/onfail/app/failurePagePath where successPagePath
     *       is either redirectUrl or the referrer and failurePagePath is always the referrer page.
     */
    private function _buildCallbackUrl($redirectUrl = ''){
        if($redirectUrl === 'session'){
            $redirectUrl = '';
        }
        if($redirectUrl){
            $redirectUrl = rtrim(urldecode($redirectUrl), '/');
            $parsedUrl = parse_url($redirectUrl);
            if($parsedUrl['scheme'] || $parseUrl['host']){
                exit(Config::getMessage(REDIRECT_PARAM_URL_ENCODED_CP_URL_MSG));
            }
        }
        //Capture the orig. page to go back to on success or on any error condition.
        //If redirectUrl isn't specified, the orig. requesting page is used for the success/failure page.
        $originalPage = $this->_getRequestingPage();
        $redirectUrl = $redirectUrl ?: $originalPage;

        return urlencode(Url::deleteParameter(ltrim("$redirectUrl/onfail$originalPage", '/'), 'session'));
    }

    /**
     * Returns the CP relative URL of the requesting page.
     * If the requesting page is outside of CP an error message is output and execution stops.
     * @return string Requesting page URL.
     */
    private function _getRequestingPage(){
        $cpBaseUrl = Url::getShortEufBaseUrl('sameAsRequest');
        if(Text::stringContains($this->referrer, $cpBaseUrl)){
            // Strip out any superflous parameters from the callback we supply to the provider
            $requestingPage = rtrim(Url::deleteParameter(Url::deleteParameter(Url::deleteParameter(Text::getSubStringAfter($this->referrer, $cpBaseUrl), 'redirect'), 'oautherror'), 'emailerror'), '/');
            // If we are left with nothing, we are on the home page, so return the default home url
            return $requestingPage ?: '/app/' . Config::getConfig(CP_HOME_URL);
        }
        // FAIL: C'mon at least you could try a little harder next time...
        exit(Config::getMessage(REQUESTING_PAGE_MUST_BE_CP_MSG));
    }

    /**
     * Goes back to the correct CP page after the authentication dance has occurred.
     * @param string $encodedRedirectUrls The encoded redirect URL string that was constructed by _buildCallbackUrl()
     * @param int $errorCode The error code of the error that has occurred
     * @param int $emailError The email error code of the email error that has occurred
     */
    private function _returnToCPPageAfterDance($encodedRedirectUrls, $errorCode = null, $emailError = null){
        $pageSegmentString = urldecode($encodedRedirectUrls);
        if(Text::beginsWith($pageSegmentString, 'app/'))
            $pageSegmentString = "/$pageSegmentString";
        list($successPage, $originalPage) = explode('/onfail', $pageSegmentString);
        if($errorCode || $emailError){
            $url = Url::addParameter($originalPage, 'redirect', urlencode(urlencode($successPage)));
            $url = ($errorCode) ? Url::addParameter($url, 'oautherror', $errorCode) : Url::addParameter($url, 'emailerror', $emailError);
        }
        else{
            $url = $successPage;
        }
        $url .= Url::sessionParameter();
        $this->client->redirectBackToCPPage($url);
    }

    /**
     * Validates the SAML assertion and logs the contact in
     *
     * @param string $token           The base64 encoded SAML Assertion
     * @param string $subject         The subject of the SAML Assertion
     * @param string $customFieldName The name of the custom field. Only used if $subject === 'CustomField'
     * @param string $redirectTarget  The URL the login request asked to go to.  We need it here so that we can send it to the
     *        controller method which ensures cookies are enabled
     * @return array An associative array of the form
     *          array(success => boolean, error => OpenLoginErrors::ERROR_CODE)
     */
    private function _loginUsingSamlToken($token, $subject, $customFieldName, $redirectTarget) {
        $token = base64_decode($token);
        if(!$token)

            return array('success' => false, 'error' => OpenLoginErrors::SAML_TOKEN_FORMAT_INVALID);

        $result = Api::sso_contact_token_validate(array(
            'token' => $token,
            'type' => SSO_TOKEN_TYPE_SAML20_RESPONSE_POST,
            'subject' => $subject,
            'cf_name' => $customFieldName,
            'url' => Url::getShortEufBaseUrl('sameAsRequest', $_SERVER['REQUEST_URI'])
        ));
        if($result['result'] !== SSO_ERR_OK)

            return array('success' => false, 'error' => OpenLoginErrors::SSO_CONTACT_TOKEN_VALIDATE_FAILED);

        $login = $result['contact_login'];

        $preHookData = array('data' => array('source' => 'SAML'));
        \RightNow\Libraries\Hooks::callHook('pre_login', $preHookData);
        $apiProfile = (object) Api::contact_federated_login(array(
            'login' => $login,
            'sessionid' => $this->session->getSessionData('sessionID'),
            'login_method' => CP_LOGIN_METHOD_SAML,
        ));
        if(!get_object_vars($apiProfile)) {
            return array('success' => false, 'error' => OpenLoginErrors::FEDERATED_LOGIN_FAILED);
        }

        if($apiProfile->disabled) {
            return array('success' => false, 'error' => OpenLoginErrors::CONTACT_DISABLED_ERROR);
        }

        $this->session->setSocialUser($apiProfile);
        $apiProfile->samlLoginUsed = true;
        $profile = $this->session->createMapping($apiProfile);
        $postHookData = array('returnValue' => $profile, 'data' => array('source' => 'SAML'));
        \RightNow\Libraries\Hooks::callHook('post_login', $postHookData);
        $this->session->createProfileCookie($profile);

        if(!$this->session->getSessionData('cookiesEnabled'))
            $this->client->redirectBackToCPPage("/ci/openlogin/ensureCookiesEnabled/" . urlencode(urlencode($redirectTarget)) . '/' . Url::sessionParameter());

        return array('success' => true);
    }

    /**
     * Ensures that the user has cookies enabled. If cookies are enabled (i.e. the user is logged in) then
     * we take them on their way. Otherwise we redirect them to an error page
     * @param string $redirectTarget Location to redirect user to after cookie check
     */
    public function ensureCookiesEnabled($redirectTarget)  {
        if(!$this->session->getSessionData('cookiesEnabled'))
            $this->client->redirectToSamlErrorUrl(OpenLoginErrors::COOKIES_REQUIRED_ERROR);

        $this->client->redirectBackToCPPage(urldecode(urldecode($redirectTarget)));
    }

    /**
     * Maps a string (presumably from a url to a constant)
     *
     * @param  string $subject The subject URL parameter specified
     * @return int    A constant to be passed to the API
     */
    private static function _mapSamlSubjectStringToConstant($subject) {
        $subject = strtolower($subject);
        if(!$subject || $subject === "contact.login")

            return SSO_TOKEN_SUBJECT_LOGIN;
        if($subject === "contact.emails.address")

            return SSO_TOKEN_SUBJECT_EMAIL;
        if($subject === "contact.id")

            return SSO_TOKEN_SUBJECT_ID;
        if(Text::beginsWith($subject, "contact.customfields."))

            return SSO_TOKEN_SUBJECT_CF;
        return null;
    }

    /**
     * Analyzes the URL passed in and calculates the values of the SAML subject,
     * redirect, and CustomFieldName parameters. Explicitly set to public to
     * enable testing, but prefixed with _ so it can't be called from a browser
     * @param  array $segments List of current URL segments
     * @return array An associative array containing the three values
     * @internal
     */
    public static function _interpretSamlArguments(array $segments) {
        //first key must be subject or redirect
        $samlSubject = $segments[3] === 'subject' ? $segments[4] : null;
        $results = array('subject' => self::_mapSamlSubjectStringToConstant($samlSubject),
                         'customFieldName' => null,
                         'redirect' => Text::getSubstringAfter(implode('/', $segments), '/redirect/', Url::getShortEufBaseUrl('sameAsRequest'))
                        );

        if(!(Text::beginsWith($results['redirect'], 'app/') || Text::beginsWith($results['redirect'], 'ci/') || Text::beginsWith($results['redirect'], 'cc/'))){
            $results['redirect'] = Url::getShortEufBaseUrl('sameAsRequest', '/app/' . Config::getConfig(CP_HOME_URL));
        }
        if($results['subject'] === SSO_TOKEN_SUBJECT_CF){
            //Custom field values look like contact.customfields.<name>, so explode it, limiting to 3 items, to get the name
            $customFieldComponents = explode('.', $samlSubject, 3);
            $customFieldName = strtolower($customFieldComponents[2]);
            $results['customFieldName'] = Text::beginsWith($customFieldName, 'c$') ? $customFieldName : 'c$' . $customFieldName;
        }

        return $results;
    }
}

/**
 * Makes HTTP requests and performs redirection.
 *
 * @internal
 */
class Client {
    const REQUEST_TIMEOUT_LENGTH = 5;

    /**
     * Performs a GET request and returns the response.
     * @param string $url Where to make the request to
     * @param array|null $headers Containing string HTTP headers to send
     * @param int &$statusCode Populated with the HTTP status code of the response
     * @return string Response
     */
    function get($url, $headers = null, &$statusCode = null) {
        return $this->makeRequest('GET', $url, $headers, $statusCode);
    }

    /**
     * Performs a POST request and returns the response.
     * @param string $url Where to make the request to
     * @param array|null $data Post data
     * @param int &$statusCode Populated with the HTTP status code of the response
     * @return string Response
     */
    function post($url, $data, &$statusCode = null) {
        return $this->makeRequest('POST', $url, $data, $statusCode);
    }

    /**
     * Redirects to the specified URL. If none is provided, redirects to CP_LOGIN_URL.
     * @param string $pageUrl The URL of the page to redirect to
     */
    function redirectBackToCPPage($pageUrl = null){
        $pageUrl = ($pageUrl) ?: Url::getShortEufBaseUrl('sameAsRequest', '/app/' . Config::getConfig(CP_LOGIN_URL));
        Framework::runSqlMailCommitHook();

        if (Url::isRedirectAllowedForHost($pageUrl)) {
            Framework::setLocationHeader((!Text::beginsWith($pageUrl, '/') && !Text::beginsWith($pageUrl, 'http')) ? "/$pageUrl" : $pageUrl);
            exit;
        }

        if (IS_PRODUCTION) {
            Framework::setLocationHeader('/app/error/error_id/' . Framework::DOCUMENT_PERMISSION);
            exit;
        }
        else {
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
            Framework::writeContentWithLengthAndExit('Host not allowed, check CP_REDIRECT_HOSTS config.');
        }
    }

    /**
     * Redirect to the SAML error URL, passing along the specified error. If the
     * config setting isn't set, the user will be taken to the generic CP error page
     * @param int    $errorID                The error code to pass along
     * @param string $urlParametersToPersist Additional URL parameters to add to the URL
     */
    function redirectToSamlErrorUrl($errorID, $urlParametersToPersist = '') {
        ActionCapture::record('saml', 'error', $errorID);
        if(($errorUrl = Config::getConfig(SAML_ERROR_URL))) {
            $errorUrl = str_ireplace('%session%', urlencode(Text::getSubstringAfter(Url::sessionParameter(), "session/")), str_ireplace('%error_code%', $errorID, $errorUrl));
            if($urlParametersToPersist !== '' && Text::endsWith($errorUrl, '/')) {
                $urlParametersToPersist = Text::getSubstringAfter($urlParametersToPersist, '/');
            }
            $errorUrl .= $urlParametersToPersist;
            Framework::runSqlMailCommitHook();
            Framework::setLocationHeader($errorUrl);
            exit;
        }
        $this->redirectBackToCPPage("/app/error/error_id/" . OpenLoginErrors::mapOpenLoginErrorsToPageErrors($errorID) . $urlParametersToPersist);
    }

    /**
     * Redirects to the specified URL that is assumed to be a third-party login site.
     * @param string $url The URL
     * @param array|null $parameters Array query string parameters
     */
    function redirectToThirdPartyLogin($url, $parameters = null){
        Framework::setLocationHeader($url . (($parameters) ? '?' . http_build_query($parameters, '', '&') : ''));
        exit;
    }

    /**
     * Sets a cookie value.
     * @param string $name  Cookie name
     * @param string $value Cookie value
     */
    function setCookie($name, $value) {
        setcookie($name, $value, 0, '/', '', Url::isRequestHttps(), true);
    }

    /**
     * Loads the curl library if it hasn't already been loaded.
     */
    function loadCurl(){
        static $curlInitialized;

        if (!isset($curlInitialized)) {
            if (!($curlInitialized = (extension_loaded('curl') || @Api::load_curl()))) {
                exit(Config::getMessage(UNABLE_TO_LOAD_CURL_LIBRARY_MSG));
            }
        }

        return $curlInitialized;
    }

    /**
     * Makes a request to the specified URL via CURL.
     * @param string $method POST or GET
     * @param string $url Location to make request to
     * @param array|null $params If $method is POST, Array of post data to send,
     * if $method is GET, Array of headers to send
     * @param int &$statusCode HTTP status code of the response
     * @return string The results of the request
     */
    private function makeRequest($method, $url, $params = null, &$statusCode = null){
        $this->loadCurl();
        $curl = curl_init();
        $options = array(
            CURLOPT_URL            => $url,
            CURLOPT_TIMEOUT        => self::REQUEST_TIMEOUT_LENGTH,
            CURLOPT_CONNECTTIMEOUT => self::REQUEST_TIMEOUT_LENGTH,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false, //required for SSL
        );
        if(is_array($params)) {
            if (strtoupper($method) === 'POST') {
                $options[CURLOPT_POSTFIELDS] = $params;
            }
            else {
                $options[CURLOPT_HTTPHEADER] = $params;
            }
        }

        curl_setopt_array($curl, $options);
        $results = curl_exec($curl);
        if ($results === false) {
            echo 'error_code: ' . curl_errno($curl);
            echo 'message: ' . curl_error($curl);
            curl_close($curl);
            exit;
        }
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $results;
    }
}
