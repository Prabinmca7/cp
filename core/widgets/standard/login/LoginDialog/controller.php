<?php

namespace RightNow\Widgets;

use RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Utils\Config;

class LoginDialog extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'has_social_user_ajax' => array(
                'method' => 'hasSocialUser',
            ),
        ));
    }

    function getData() {
        $siteConfigValue = \RightNow\Utils\Framework::getSiteConfigValue('CP.EmailConfirmationLoop.Enable');

        // We need to remove password field if 'CP.EmailConfirmationLoop.Enable' config is set to 1.
        if($siteConfigValue === 1) {
            $this->data['attrs']['create_account_fields'] = "Contact.Emails.PRIMARY.Address;Contact.Login;CommunityUser.DisplayName;Contact.FullName";

            // if cookie is set, display message dialog and delete cookie.
            if(isset($_COOKIE['cp_createUser'])) {
                $this->data['js']['email_password_message'] = $this->data['attrs']['email_password_message'];
                setcookie('cp_createUser', '2', time() - 86400, '/', '', \RightNow\Utils\Config::getConfig(SEC_END_USER_HTTPS, 'COMMON'), true);
            }
        }

        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $this->data['username'] = Url::getParameter('username');
        $this->data['currentPage'] = $_SERVER['REQUEST_URI'];

        if ($redirectOverride = $this->getRedirectOverride()) {
            $this->data['js']['redirectOverride'] = $redirectOverride;
        }

        if ($loginLinkOverride = $this->makeRedirectSecure($redirectOverride ?: $this->getRedirectPage())) {
            $this->data['js']['loginLinkOverride'] = $loginLinkOverride;
        }

        //honor: (1) attribute's value (if it's set to true) (2) config
        $this->data['attrs']['disable_password'] = $this->data['attrs']['disable_password'] ?: !Config::getConfig(EU_CUST_PASSWD_ENABLED);
        $this->data['create_account_fields'] = $this->getCreateAccountFields($this->data['attrs']['create_account_fields']);

        if ($this->data['attrs']['open_login_providers']) {
            $this->classList->add('rn_AdditionalOpenLogin');
            $this->data['attrs']['open_login_providers'] = array_filter(explode(',', $this->data['attrs']['open_login_providers']), 'strtolower');
        }
    }

    /**
     * Return an array of field names extracted from 'create_account_fields' attribute.
     * @param string $fieldString The semi-colon separated string of field names from 'create_account_fields'.
     * @return Array A list of field names used in the create account form.
     */
    function getCreateAccountFields($fieldString) {
        $fields = array();
        foreach($fieldString ? array_filter(array_map('trim', explode(';', $fieldString))) : array() as $field) {
            if (strtolower($field) === 'contact.fullname') {
                $names = array('Contact.Name.First', 'Contact.Name.Last');
                if (Config::getConfig(intl_nameorder)) {
                    $names = array_reverse($names);
                }
                $fields = array_merge($fields, $names);
            }
            else {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Check if contact has a social user profile. Echos JSON encoded result
     */
    function hasSocialUser() {
        if(!\RightNow\Utils\Framework::isLoggedIn()){
            $errors[] = Config::getMessage(CONTACT_IS_NOT_LOGGED_IN_MSG);
        }
        else {
            $socialUser = $this->CI->model('CommunityUser')->get()->result;
        }

        $results = array(
            'socialUser' => $socialUser ? $socialUser->ID : '',
            'errors' => $errors,
        );

        echo json_encode($results);
    }

    /**
     * Normalizes the value found in the 'redirect' URL parameter.
     * @return string|null redirect page or null if none found in the URL
     */
    protected function getRedirectOverride() {
        if ($redirectLocation = Url::getParameter('redirect')) {
            //We need to check if the redirect location is a fully qualified URL, or just a relative one
            $redirectLocation = urldecode(urldecode($redirectLocation));
            $parsedURL = parse_url($redirectLocation);

            if ((isset($parsedURL['scheme']) && $parsedURL['scheme']) || (Text::beginsWith($parsedURL['path'], '/ci/') || Text::beginsWith($parsedURL['path'], '/cc/'))) {
                return $redirectLocation;
            }

            return Text::beginsWith($redirectLocation, '/app/') ? $redirectLocation : "/app/$redirectLocation";
        }
    }

    /**
     * Returns the redirect_url attribute or the current page
     * if the attribute is blank.
     * @return string redirect page
     */
    protected function getRedirectPage() {
        return $this->data['attrs']['redirect_url'] ?: $this->data['currentPage'];
    }

    /**
     * Changes the given redirect location to
     * a fully-qualified URL with HTTPS if the
     * current request isn't secure and CP_FORCE_PASSWORDS_OVER_HTTPS
     * is enabled.
     * @param string $redirect Redirect page
     * @return string|null Https link or null if $redirect doesn't need to change
     */
    protected function makeRedirectSecure($redirect) {
        if (Config::getConfig(CP_FORCE_PASSWORDS_OVER_HTTPS) && !Url::isRequestHttps()) {
            $page = Url::getShortEufBaseUrl('sameAsRequest', '/app/' . Config::getConfig(CP_LOGIN_URL) . Url::sessionParameter());
            $redirect = urlencode(urlencode($redirect));

            return Url::addParameter($page, 'redirect', $redirect);
        }
    }

}
