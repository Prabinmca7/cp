<?php

use RightNow\UnitTest\Fixture,
    RightNow\UnitTest\Helper;

class TestSocialBookmarkLink extends WidgetTestCase {

    public $testingWidget = "standard/utils/SocialBookmarkLink";

    function __construct () {
        parent::__construct();
        $this->fixtureInstance = new Fixture();
    }

    function testCheckQuestionExist () {

        // Error message is displayed when a deleted question is attempted to be shared
        $questionDeleted = $this->fixtureInstance->make('QuestionDeleted');
        $instance = $this->createWidgetInstance();
        $cookies = Helper::logInUser('useradmin');

        $response = $this->callAjaxMethod('checkQuestionExist', array('qid' => $questionDeleted->ID, 'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0)), true, $this->widgetInstance, array(), true, $cookies);

        $this->assertNull($response->result);
        $this->assertIdentical($response->errors[0]->externalMessage, 'Cannot find question, which may have been deleted by another user.');

        Helper::logOutUser('useradmin', $cookies['rawSession']);
        $this->fixtureInstance->destroy();

        // Sharing an active question doesn't display any errors.
        $questionActive = $this->fixtureInstance->make('QuestionActiveAllBestAnswer');
        $cookies = Helper::logInUser('useradmin');

        $response = $this->callAjaxMethod('checkQuestionExist', array('qid' => $questionActive->ID, 'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0)), true, $this->widgetInstance, array(), true, $cookies);

        $this->assertNotNull($response->result);
        $this->assertNull($response->errors);

        Helper::logOutUser('useradmin', $cookies['rawSession']);
        $this->fixtureInstance->destroy();
    }

    function testGetData() {
        $instance = $this->createWidgetInstance(array('object_type' => 'question'));
        $questionSuspended = $this->fixtureInstance->make('QuestionSuspendedBestAnswer');

        $this->logIn('modactive1');
        $this->addUrlParameters(array('qid' => $questionSuspended->ID));
        $data = $this->getWidgetData();

        // For non-active question, hide the share link
        $this->assertNull($instance->classList->classes[1]);
        $this->logOut();
        $this->fixtureInstance->destroy();
    }
}
