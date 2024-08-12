<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SocialContentFlaggingTest extends WidgetTestCase {
    public $testingWidget = 'standard/feedback/SocialContentFlagging';

    function tearDown () {
        $this->logOut();
    }

    function testDoesNotRenderWithInvalidID(){
        $questionID = $this->getFixtures(array('QuestionActiveAllBestAnswer'))->ID;
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => 35233));
        $this->assertFalse($this->widgetInstance->getData());

        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => $questionID, 'comment_id' => 35233));
        $this->assertFalse($this->widgetInstance->getData());
    }

    function testDoesNotRenderWhenSocialUserIsTheContentAuthor(){
        list($fixtureInstance, $question, $user) = $this->getFixtures(array('QuestionActiveAllBestAnswer', 'UserActive1'));
        $this->logIn($user->Contact->Login);

        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question->ID));
        $this->assertFalse($this->widgetInstance->getData());

        $this->logOut();
        $fixtureInstance->destroy();
    }

    function testRenderBasedOnUserProfile () {
        //Should render when no user is logged in
        list($fixtureInstance, $question, $comment) = $this->getFixtures(array('QuestionActiveAllBestAnswer', 'CommentActiveUserActive'));
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question->ID));
        $this->assertNull($this->widgetInstance->getData());

        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => $comment->ID));
        $this->assertNull($this->widgetInstance->getData());
        $fixtureInstance->destroy();

        //Render with user without social user
        $this->logIn('weide@rightnow.com.invalid');
        list($fixtureInstance, $question, $comment) = $this->getFixtures(array('QuestionActiveAllBestAnswer', 'CommentActiveUserActive'));
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question->ID));
        $this->assertNull($this->widgetInstance->getData());
        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => $comment->ID));
        $this->assertNull($this->widgetInstance->getData());
        $this->logOut();
        $fixtureInstance->destroy();

        //Render with user with social user
        list($fixtureInstance, $question, $comment) = $this->getFixtures(array('QuestionActiveAllBestAnswer', 'CommentActiveUserActive'));
        $this->logIn('mzoeller@rightnow.com.invalid');
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question->ID));
        $this->assertNull($this->widgetInstance->getData());
        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => $comment->ID));
        $this->assertNull($this->widgetInstance->getData());
        $this->logOut();
        $fixtureInstance->destroy();
    }

    function testRenderBasedOnStatus(){
        //No render on locked questions
        list($fixtureInstance, $question, $comment) = $this->getFixtures(array('QuestionLockedUserActive', 'CommentActiveModActive'));
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question->ID));
        $this->assertFalse($this->widgetInstance->getData());
        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => $comment->ID));
        $this->assertFalse($this->widgetInstance->getData());
        $fixtureInstance->destroy();

        //No render on non-active questions
        list($fixtureInstance, $question1, $question2, $question3) = $this->getFixtures(array('QuestionSuspendedModActive', 'QuestionPending', 'QuestionDeleted'));
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question1->ID));
        $this->assertFalse($this->widgetInstance->getData());
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question2->ID));
        $this->assertFalse($this->widgetInstance->getData());
        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question3->ID));
        $this->assertFalse($this->widgetInstance->getData());
        $fixtureInstance->destroy();

        //No render on non-active comments
        list($fixtureInstance, $question, $comment1, $comment2) = $this->getFixtures(array('QuestionActiveAllBestAnswer', 'CommentSuspendedModActive', 'CommentPendingUserActive'));
        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => $comment1->ID));
        $this->assertFalse($this->widgetInstance->getData());

        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => $comment2->ID));
        $this->assertFalse($this->widgetInstance->getData());
        $fixtureInstance->destroy();
    }

    function testFlagsAreRetrieved() {
        list($fixtureInstance, $question) = $this->getFixtures(array('QuestionActiveAllBestAnswer'));

        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question->ID));
        $return = $this->getWidgetData();
        $this->assertTrue(count($return['js']['flags']) > 0);
        foreach ($return['js']['flags'] as $flagInfo) {
            $this->assertFalse($flagInfo->Selected);
            $this->assertIsA($flagInfo->ID, 'int');
            $this->assertIsA($flagInfo->LookupName, 'string');
        }

        $fixtureInstance->destroy();
    }

    function testProperFlagIsSelectedForAlreadyFlaggedComment () {
        list($fixtureInstance, $question, $comment) = $this->getFixtures(array('QuestionActiveAllBestAnswer', 'CommentActiveUserActive'));

        $this->logIn('useractive1');

        $flagResult = $this->CI->model('CommunityComment')->flagComment($comment->ID);

        $this->createWidgetInstance(array('content_type' => 'comment', 'question_id' => $question->ID, 'comment_id' => $comment->ID));
        $widgetData = $this->getWidgetData();
        $this->assertTrue($widgetData['js']['flags'][0]->Selected);

        $this->destroyObject($flagResult->result);
        $this->logOut();

        $fixtureInstance->destroy();
    }

    function testProperFlagIsSelectedForAlreadyFlaggedQuestion () {
        list($fixtureInstance, $question) = $this->getFixtures(array('QuestionActiveAllBestAnswer'));

        $this->logIn('modactive1');

        $flagResult = $this->CI->model('CommunityQuestion')->flagQuestion($question->ID);

        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question->ID));
        $widgetData = $this->getWidgetData();
        $this->assertTrue($widgetData['js']['flags'][0]->Selected);

        $this->destroyObject($flagResult->result);
        $this->logOut();

        $fixtureInstance->destroy();
    }

    function testFlagLimiting () {
        list($fixtureInstance, $question) = $this->getFixtures(array('QuestionActiveAllBestAnswer'));

        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question->ID, 'flag_types' => 'spam'));
        $widgetData = $this->getWidgetData();
        $flags = $widgetData['js']['flags'];
        $this->assertSame(1, count($flags));
        $this->assertIdentical('Spam', $flags[0]->LookupName);

        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question->ID, 'flag_types' => 'spam,miscategorized'));
        $widgetData = $this->getWidgetData();
        $flags = $widgetData['js']['flags'];
        $this->assertSame(2, count($flags));
        $this->assertIdentical('Spam', $flags[0]->LookupName);
        $this->assertIdentical('Miscategorized', $flags[1]->LookupName);

        $this->createWidgetInstance(array('content_type' => 'question', 'question_id' => $question->ID, 'flag_types' => 'spam,inappropriate,redundant,miscategorized'));
        $widgetData = $this->getWidgetData();
        $flags = $widgetData['js']['flags'];
        $this->assertSame(4, count($flags));
        $this->assertIdentical('Spam', $flags[0]->LookupName);
        $this->assertIdentical('Inappropriate', $flags[1]->LookupName);
        $this->assertIdentical('Redundant', $flags[2]->LookupName);
        $this->assertIdentical('Miscategorized', $flags[3]->LookupName);

        $fixtureInstance->destroy();
    }
}
