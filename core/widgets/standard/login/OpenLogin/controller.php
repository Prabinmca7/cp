<?php
namespace RightNow\Widgets;

use RightNow\Utils\Text,
    RightNow\Utils\Config;

class OpenLogin extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData(){
        if (!$this->verifyOAuthConfigs() || !$this->verifyOpenIDUrl()) return false;

        if($redirectParam = \RightNow\Utils\Url::getParameter('redirect')){
            //check if the redirect location is a fully-qualified or relative
            $redirectLocation = urldecode(urldecode($redirectParam));
            $parsedURL = parse_url($redirectLocation);

            if ((!isset($parsedURL['scheme']) || !$parsedURL['scheme']) &&
                !Text::beginsWith($parsedURL['path'], '/ci/') &&
                !Text::beginsWith($parsedURL['path'], '/cc/') &&
                !Text::beginsWith($redirectLocation, '/app/')) {
                $redirectLocation = "/app/$redirectLocation";
            }
            $this->data['attrs']['redirect_url'] = $redirectLocation;
        }
        if(!Text::endsWith($this->data['attrs']['controller_endpoint'], '/'))
            $this->data['attrs']['controller_endpoint'] .= '/';

        if($errorCode = \RightNow\Utils\Url::getParameter('oautherror')){
            require_once CPCORE . 'Libraries/OpenLoginErrors.php';
            $this->data['js']['error'] = \RightNow\Libraries\OpenLoginErrors::getErrorMessage($errorCode);
        }
        $this->data['attrs']['redirect_url'] = \RightNow\Utils\Url::deleteParameter($this->data['attrs']['redirect_url'], 'oautherror');
    }

    /**
     * Verifies that if the widget attempts to authenticate
     * using OAuth, that the proper OAuth configs have been set.
     * An error message is reported if one of these unset OAuth configs
     * is found.
     * @return boolean True if configs check out, False if there's a problem
     */
    protected function verifyOAuthConfigs() {
        $oauthProviders = array(
            'facebook' => array(
                'endpoint' => '/ci/openlogin/oauth/authorize/facebook',
                'configs' => array(
                    'value' => array(FACEBOOK_OAUTH_APP_ID, FACEBOOK_OAUTH_APP_SECRET),
                    'label' => array('<m4-ignore>FACEBOOK_OAUTH_APP_ID</m4-ignore>', '<m4-ignore>FACEBOOK_OAUTH_APP_SECRET</m4-ignore>'),
                ),
            ),
            'twitter'  => array(
                'endpoint' => '/ci/openlogin/oauth/authorize/twitter',
                'configs' => array(
                    'value' => array(TWITTER_OAUTH_APP_ID, TWITTER_OAUTH_APP_SECRET),
                    'label' => array('<m4-ignore>TWITTER_OAUTH_APP_ID</m4-ignore>', '<m4-ignore>TWITTER_OAUTH_APP_SECRET</m4-ignore>'),
                ),
            ),
            'google'   => array(
                'endpoint' => '/ci/openlogin/openid/authorize/google',
                'configs' => array(
                    'value' => array(GOOGLE_OAUTH_APP_ID, GOOGLE_OAUTH_APP_SECRET),
                    'label' => array('<m4-ignore>GOOGLE_OAUTH_APP_ID</m4-ignore>', '<m4-ignore>GOOGLE_OAUTH_APP_SECRET</m4-ignore>'),
                ),
            ),
        );
        $buttonLabel = trim(strtolower($this->data['attrs']['label_service_button']));
        $endpoint = trim(strtolower($this->data['attrs']['controller_endpoint']));

        foreach ($oauthProviders as $providerName => $provider) {
            $configs = $provider['configs'];
            if (($buttonLabel === $providerName || Text::stringContains($endpoint, $provider['endpoint'])) &&
                (!Config::getConfig($configs['value'][0]) || !Config::getConfig($configs['value'][1]))) {
                $this->reportError(sprintf(Config::getMessage(PCT_S_PCT_S_CONFIG_SETTINGS_MSG), $configs['label'][0], $configs['label'][1]));
                return false;
            }
        }

        return true;
    }

    /**
     * Verifies that the `preset_openid_url` value
     * contains a '[username]' placeholder in it.
     * @return boolean True if the attribute checks out, false if it doesn't
     */
    protected function verifyOpenIDUrl() {
        if ($this->data['attrs']['openid'] && $this->data['attrs']['preset_openid_url']){
            $keyToReplace = '[username]';
            if (!Text::stringContains($this->data['attrs']['preset_openid_url'], $keyToReplace)){
                echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_DOESNT_CONTAIN_PCT_S_VALUE_LBL), 'preset_openid_url', $keyToReplace));
                return false;
            }
        }

        return true;
    }
}
