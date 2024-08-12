<?

use RightNow\UnitTest\Helper,
    RightNow\UnitTest\Fixture;

Helper::loadTestedFile(__FILE__);

class TestQuestionDetailWidget extends WidgetTestCase {
    public $testingWidget = "standard/discussion/QuestionDetail";
    public $testingFile = __FILE__;
    public $testingClass = __CLASS__;

    function setUp() {
        $this->logIn();
        $this->fixtureInstance = new Fixture();
    }

    function tearDown() {
        $this->logOut();
        $this->fixtureInstance->destroy();
    }
    
    function testGetData() {
        $question = $this->fixtureInstance->make('QuestionActiveModActive');
        $this->addUrlParameters(array('qid' => $question->ID));
        $this->logIn($question->CreatedByCommunityUser->Contact->Login);

        $this->createWidgetInstance(array('author_roleset_callout' => "5|Posted by a moderator; 1,7|Posted by an admin"));
        $widgetData = $this->getWidgetData();
        
        $this->assertEqual($widgetData['author_roleset_callout'], array(
            '5' => 'Posted by a moderator',
            '1' => 'Posted by an admin',
            '7' => 'Posted by an admin',
        ));
        $this->assertEqual($widgetData['author_roleset_styling'], array(
            'Posted by a moderator' => 'rn_AuthorStyle_1',
            'Posted by an admin' => 'rn_AuthorStyle_2',
        ));

        $this->logOut();
        $this->restoreUrlParameters();
        $this->fixtureInstance->destroy();
    }

    function testDelete() {
        // don't allow session cookie to be set, since it causes issues when running tests from test.py
        $CI = get_instance();
        $sessionClass = new \ReflectionClass('RightNow\Libraries\Session');
        $previousCanSetSessionCookie = Helper::getInstanceProperty($sessionClass, $CI->session, 'canSetSessionCookie');
        Helper::setInstanceProperty($sessionClass, $CI->session, 'canSetSessionCookie', false);

        $question = $this->fixtureInstance->make('QuestionActiveModActive');
        $this->createWidgetInstance();

        $response = $this->callWidgetMethodViaWgetRecipient('delete', array('questionID' => $question->ID), true, $this->widgetInstance, 'modactive1')->result;
        $this->assertSame($question->ID, $response->ID);
        $this->assertSame($CI->session->getFlashData('info'), $widget->data['attrs']['successfully_deleted_question_banner']);

        Helper::setInstanceProperty($sessionClass, $CI->session, 'canSetSessionCookie', $previousCanSetSessionCookie);
    }

    function testAvatarDataForActiveUser() {
        list($fixtureInstance, $question) = $this->getFixtures(array('QuestionActiveAllBestAnswer'));
        $this->addUrlParameters(array('qid' => $question->ID));
        $instance = $this->createWidgetInstance();
        $data = $this->getWidgetData($instance);
        $this->assertEqual('/app/public_profile/user/' . $question->CreatedByCommunityUser->ID . '/' . \RightNow\Utils\Url::sessionParameter(), $data['profileUrl']);
        $this->assertEqual('Active User1', $data['author']);
        $this->assertEqual('rn_DisplayName', $data['authorClassList']);
        $this->restoreUrlParameters();
        $fixtureInstance->destroy();
    }

    function testAvatarDataForInactiveUser() {
        list($fixtureInstance, $question) = $this->getFixtures(array('QuestionActiveUserSuspended'));
        $this->addUrlParameters(array('qid' => $question->ID));
        $instance = $this->createWidgetInstance();
        $data = $this->getWidgetData($instance);
        $this->assertNull($data['profileUrl']);
        $this->assertEqual('[inactive]', $data['author']);
        $this->assertEqual('rn_DisplayName rn_DisplayNameDisabled', $data['authorClassList']);
        $this->restoreUrlParameters();
        $fixtureInstance->destroy();
    }    
}
