<?php

namespace RightNow\Controllers;

use RightNow\Utils\Framework,
    RightNow\Utils\Config,
    RightNow\Utils\Url;

/**
 * Endpoint to handle SSO between CP and the community
 */
final class Social extends Base
{
    const SSO_ERROR_DEFAULT             = 4;
    const SSO_ERROR_NO_CID              = 5;
    const SSO_ERROR_NO_EMAIL            = 9;
    const SSO_ERROR_NO_NAME             = 10;
    const SSO_ERROR_DUP_EMAIL           = 11;
    const SSO_ERROR_UNKNOWN             = 12;
    const SSO_ERROR_INVALID_SIG_VERSION = 13;
    const SSO_ERROR_INVALID_SIG         = 14;
    const SSO_ERROR_TOKEN_USED          = 15;
    const SSO_ERROR_TOKEN_EXPIRED       = 16;
    const SSO_ERROR_TOKEN_IP_MISMATCH   = 17;

    public function __construct()
    {
        parent::__construct();

        // Allow account creation, account recovery, and login stuff for users who aren't logged in if CP_CONTACT_LOGIN_REQUIRED is on.
        parent::_setMethodsExemptFromContactLoginRequired(array(
            'login',
            'logout',
            'ssoRedirect',
            'ssoError'
        ));

    }

    /**
     * Entry point for when a user hits a community page which requires the user
     * to be logged in, and the user isn't currently logged in.
     *
     * @param string $redirectUrl The URL that the user attempted to navigate to within the community
     */
    public function login($redirectUrl = null)
    {
        if (!$redirectUrl) {
            $this->ssoError();
        }
        //Decode URL since it can contain query string parameters which we will escape for XSS security
        $redirectUrl = htmlspecialchars_decode(urldecode($redirectUrl));
        if(Framework::isLoggedIn())
            $this->ssoRedirect(base64_encode($redirectUrl));
        $loginUrl = $this->_generateLoginUrl($redirectUrl);
        Framework::setLocationHeader($loginUrl);
        exit;
    }

    /**
     * Exit point for when a user logs out from within the community
     * @param string|null $redirectUrl URL to redirect user to after logout within the community
     */
    public function logout($redirectUrl = null)
    {
        Url::redirectToHttpsIfNecessary();

        $redirectUrl = urldecode($redirectUrl);

        if(!$redirectUrl){
            $redirectUrl = Config::getConfig(COMMUNITY_HOME_URL);
        }

        if(Config::getConfig(PTA_EXTERNAL_LOGOUT_SCRIPT_URL)){
            $redirectUrl = str_ireplace('%source_page%', urlencode($redirectUrl), Config::getConfig(PTA_EXTERNAL_LOGOUT_SCRIPT_URL));
        }

        $this->model('Contact')->doLogout('');
        Framework::setLocationHeader($redirectUrl);
        exit;
    }

    /**
     * Redirect point once a user has logged into CP and we're redirecting them
     * to the community with their account information.
     *
     * @param string $redirectUrl Community URL where the user should go after getting logged in. This value is expected to be base_64 encoded.
     */
    public function ssoRedirect($redirectUrl = null)
    {
        $results = $this->_validateRedirect($redirectUrl);
        if (array_key_exists('error', $results)) {
            $this->ssoError($results['error']);
        }

        \RightNow\ActionCapture::record('community', 'login');
        Framework::setLocationHeader($results['location']);
        exit;
    }

    /**
     * Validates $redirectURL and returns an array with a 'location' key on success or an 'error' key on error.
     * @param string|null $redirectUrl A url encoded, base64 encoded redirect URL.
     * @return array An associative array having a 'location' key on success or an 'error' key on error.
     */
    private function _validateRedirect($redirectUrl = null)
    {
        if (!$redirectUrl) {
            return array('error' => null);
        }

        list($redirectUrl, $hashFragment) = $this->_parseRedirect($redirectUrl);

        // For security purposes, ensure that the location we're going to has the same hostname as the community.
        $urlDetails = parse_url($redirectUrl);
        $communityUrl = parse_url(Config::getConfig(COMMUNITY_BASE_URL));
        if ($urlDetails['host'] !== $communityUrl['host']) {
            return array('error' => self::SSO_ERROR_UNKNOWN);
        }

        if (!Framework::isLoggedIn()) {
            return array('location' => $this->_generateLoginUrl("{$redirectUrl}{$hashFragment}"));
        }

        if (!($ssoToken = Url::communitySsoToken('', false, '', false))) {
            return array('error' => $this->_getTokenError());
        }

        return array('location' => $redirectUrl . (\RightNow\Utils\Text::stringContains($redirectUrl, '?') ? '&' : '?') . "opentoken={$ssoToken}{$hashFragment}");
    }

    /**
     * Parses $redirectUrl and returns a two-element array containing the decoded redirect URL and the hash fragment/anchor tag if it exists.
     * @param string|null $redirectUrl A url and base64 encoded redirect URL.
     * @return array A two-element array containing the decoded redirect url, and the hash fragment if it exists.
     */
    private function _parseRedirect($redirectUrl) {
        $hashFragment = '';
        if (($decodedUrl = urldecode(base64_decode($redirectUrl))) && preg_match('/^.+(#[0-9]+)$/', $decodedUrl, $matches)) {
            $hashFragment = $matches[1];
            $decodedUrl = substr($decodedUrl, 0, -strlen($hashFragment));
        }

        return array($decodedUrl, $hashFragment);
    }

    /**
     * Returns the appropriate error when an SSO token cannot be obtained.
     * @return int One of the SSO_ERROR_* defines
     */
    private function _getTokenError()
    {
        $fieldMapping = array(
            'email'     => self::SSO_ERROR_NO_EMAIL,
            'firstName' => self::SSO_ERROR_NO_NAME,
            'lastName'  => self::SSO_ERROR_NO_NAME,
        );

        foreach($fieldMapping as $field => $error) {
            if ($this->session->getProfileData($field) === '') {
                return $error;
            }
        }

        return self::SSO_ERROR_UNKNOWN;
    }

    /**
     * SSO login error routing function. Parses the passed in error and redirects to the error page.
     * @param int|null $errorCode Error code number
     */
    public function ssoError($errorCode = null)
    {
        // Error codes that are expected to be prepended with 'sso'
        $ssoCodes = array(
            self::SSO_ERROR_NO_EMAIL,
            self::SSO_ERROR_NO_NAME,
            self::SSO_ERROR_DUP_EMAIL,
            self::SSO_ERROR_INVALID_SIG_VERSION,
            self::SSO_ERROR_INVALID_SIG,
            self::SSO_ERROR_TOKEN_USED,
            self::SSO_ERROR_TOKEN_EXPIRED,
            self::SSO_ERROR_TOKEN_IP_MISMATCH,
        );

        Framework::setLocationHeader('/app/error/error_id/' . (in_array($errorCode, $ssoCodes) ? "sso{$errorCode}" : self::SSO_ERROR_DEFAULT));
        exit;
    }

    /**
     * Generates the CP login URL. This can either be within CP or to the
     * customers site, if they are using PTA.
     * @param string $url The redirect URL to return to after they have logged in
     * @return string The finalized redirect URL
     */
    private function _generateLoginUrl($url)
    {
        $redirectUrl = '/ci/social/ssoRedirect/' . base64_encode($url);
        return Url::replaceExternalLoginVariables(0, $redirectUrl)
            ?: Url::getShortEufBaseUrl('sameAsRequest', '/app/' . Config::getConfig(CP_LOGIN_URL) . '/redirect/' . urlencode($redirectUrl) . Url::sessionParameter());
    }
}
