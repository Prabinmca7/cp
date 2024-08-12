<?

use RightNow\Utils\Config,
    RightNow\UnitTest\Helper;

Helper::loadTestedFile(__FILE__);

class OpenLoginWidgetTest extends WidgetTestCase {
    public $testingWidget = 'standard/login/OpenLogin';

    function setUp() {
        parent::setUp();
        $this->configs = Helper::getConfigValues(array(
            'FACEBOOK_OAUTH_APP_ID',
            'FACEBOOK_OAUTH_APP_SECRET',
            'TWITTER_OAUTH_APP_ID',
            'TWITTER_OAUTH_APP_SECRET',
            'GOOGLE_OAUTH_APP_ID',
            'GOOGLE_OAUTH_APP_SECRET',
        ));
    }

    function tearDown() {
        parent::tearDown();
        Helper::setConfigValues($this->configs);
    }

    function verifyConfigChecking ($config1, $config2, $controllerEndpoint, $serviceButton) {
        // The widget checks both the endpoint...
        $widget = $this->createWidgetInstance(array('controller_endpoint' => $controllerEndpoint, 'label_service_button' => 'blah'));

        // Neither config set.
        \Rnow::updateConfig($config1, null, true);
        \Rnow::updateConfig($config2, null, true);
        $this->assertFalse($widget->getData());

        // ID not set.
        \Rnow::updateConfig($config2, 'love', true);
        $this->assertFalse($widget->getData());

        // Both configs set.
        \Rnow::updateConfig($config1, 'all', true);
        $this->assertNull($widget->getData());

        // ...and the button label.
        $this->setWidgetAttributes(array('controller_endpoint' => 'blah', 'label_service_button' => $serviceButton));

        // Both configs set.
        $this->assertNull($widget->getData());

        // Neither config set.
        \Rnow::updateConfig($config1, null, true);
        \Rnow::updateConfig($config2, null, true);
        $this->assertFalse($widget->getData());

        // ID not set.
        \Rnow::updateConfig($config2, 'love', true);
        $this->assertFalse($widget->getData());
    }

    function testErrorWithUnsetOAuthFBConfigs() {
        $this->verifyConfigChecking('FACEBOOK_OAUTH_APP_ID', 'FACEBOOK_OAUTH_APP_SECRET', '/ci/openlogin/oauth/authorize/facebook   ', ' FACEBOOK  ');
    }

    function testErrorWithUnsetOAuthTwitterConfigs() {
        $this->verifyConfigChecking('TWITTER_OAUTH_APP_ID', 'TWITTER_OAUTH_APP_SECRET', '/ci/openlogin/oauth/authorize/twitter   ', ' TWITTER  ');
    }

    function testErrorWithUnsetOAuthGoogleConfigs() {
        $this->verifyConfigChecking('GOOGLE_OAUTH_APP_ID', 'GOOGLE_OAUTH_APP_SECRET', '/ci/openlogin/openid/authorize/google   ', ' Google  ');
    }
}
