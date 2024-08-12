<?php

use RightNow\UnitTest\Helper;

Helper::loadTestedFile(__FILE__);

class ContactUsTest extends WidgetTestCase {
    public $testingWidget = 'standard/utils/ContactUs';

    function testConstructChannelData () {
        $instance = $this->createWidgetInstance();
        $method = $this->getWidgetMethod('constructChannelData', $instance);
        $channelData = $method(array('question', 'community', 'feedback'));
        $this->assertEqual('/app/ask', $channelData['question']['url']);
        $this->assertEqual('/app/social/ask', $channelData['community']['url']);
        $this->assertEqual('', $channelData['feedback']['url']);
    }

    function testAddUrlParametersToSelectChannels() {
        $instance = $this->createWidgetInstance();
        $method = $this->getWidgetMethod('addUrlParametersToSelectChannels', $instance);
        $this->assertEqual('/app/ask', $method('question', '/app/ask'));
        $this->assertEqual('/app/social/ask', $method('community', '/app/social/ask'));
        $this->assertEqual('whatever', $method('feedback', 'whatever'));

        // Note: as Url::getParametersFromList() does not respect `$this->addUrlParameters`, we would need to construct a mock CI, like is done from Url.test.php, _and_
        // be able to pass that into this method, which seems a little over-kill, so we are not testing here when url parameters are present.
        // However, the rendering tests _do_ verify this widget tacks on `p` and/or `c` url parameters.
    }

    function testGetChannelList() {
        $instance = $this->createWidgetInstance();
        $method = $this->getWidgetMethod('getChannelList', $instance);
        $allChannels = array('question', 'community', 'chat', 'feedback');
        $originalConfigs = Helper::getConfigValues(array('MOD_CHAT_ENABLED', 'CP_CONTACT_LOGIN_REQUIRED'));

        // make sure that MOD_CHAT_ENABLED is disabled
        Helper::setConfigValues(array('MOD_CHAT_ENABLED' => 0));

        // No channels
        $results = $method(array());
        $this->assertIdentical(array(), $results);

        // subset
        $results = $method(array('question'));
        $this->assertIdentical(array('question'), $results);

        // default, not logged in
        $results = $method($allChannels);
        $this->assertIdentical(array('question', 'community', 'feedback'), $results);

        // Feedback not presented when user not logged in and CP_CONTACT_LOGIN_REQUIRED is set
        Helper::setConfigValues(array('CP_CONTACT_LOGIN_REQUIRED' => 1));
        $results = $method($allChannels);
        $this->assertIdentical(array('question', 'community'), $results);

        // Feedback presented as a channel when user is logged in
        $this->logIn();
        $results = $method($allChannels);
        $this->assertIdentical(array('question', 'community', 'feedback'), $results);
        $this->logOut();
        Helper::setConfigValues($originalConfigs);

        // chat presented when MOD_CHAT_ENABLED
        Helper::setConfigValues(array('MOD_CHAT_ENABLED' => 1));
        $results = $method($allChannels);
        $this->assertIdentical($allChannels, $results);
        Helper::setConfigValues($originalConfigs);

        // All channels presented when `*_link_always_displayed` attributes set
        $instance = $this->createWidgetInstance(array('chat_link_always_displayed' => true, 'feedback_link_always_displayed' => true));
        $method = $this->getWidgetMethod('getChannelList', $instance);
        $results = $method($allChannels);
        $this->assertIdentical($allChannels, $results);
        // and only when chat and feedback specified in channels
        $results = $method(array('question', 'community'));
        $this->assertIdentical(array('question', 'community'), $results);
    }
}