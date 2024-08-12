<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Connect\v1_4 as Connect,
    RightNow\Libraries\Decorator,
    RightNow\Libraries\TabularDataObject,
    RightNow\Libraries\ConnectTabular,
    RightNow\Decorators;

class SocialCommentPermissionsTest extends CPTestCase {
    public $testingClass = 'RightNow\Decorators\SocialCommentPermissions';

    function getMockedComment(array $fields){
        $question = new MockedSocialCommentForCommentTest();
        foreach($fields as $key => $value){
            $question->$key = $value;
        }
        return $question;
    }

    function clearCache($decoratorInstance){
        $cache = new \ReflectionProperty($decoratorInstance, 'cache');
        $cache->setAccessible(true);
        $cache->setValue($decoratorInstance, array());
    }

    /**
     * Creates a question and comment as the given user.  Logs out when done.
     * Takes a username, returns an array with the question and comment
     * Usage: list($question, $comment) = $this->createQuestionAndCommentAsUser('useractive1', 'Suspended');
     */
    function createQuestionAndCommentAsUser($userName, $questionStatus = 'Active') {
        $this->logIn($userName);
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__),
            'CommunityQuestion.StatusWithType.Status.LookupName' => (object) array('value' => 'Active'),
        )))->result;
        $comment = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
        )))->result;
        $this->logOut();

        // for the most part, since questions cannot be created with a status
        // other than active, manually change status if not active.
        if($questionStatus !== 'Active') {
            $question->StatusWithType->Status = new Connect\NamedIDOptList();
            $question->StatusWithType->Status->LookupName = $questionStatus;
            $question->save();
        }

        $this->clearCache($question->SocialPermissions);
        $this->clearCache($comment->SocialPermissions);
        return array($question, $comment);
    }

    function setQuestionOnDecorator($decoratorInstance, $question){
        $decoratorQuestion = new \ReflectionProperty($decoratorInstance, 'socialQuestion');
        $decoratorQuestion->setAccessible(true);
        $decoratorQuestion->setValue($decoratorInstance, $question);
    }

    function setMockQuestionOnDecorator($decoratorInstance, $questionPermissionResults, $question = null){
        $this->setQuestionOnDecorator($decoratorInstance, new MockSocialQuestionForCommentText($questionPermissionResults));
    }

    /**
     * Sets the status of the given question to the given value and clears the permission cache.
     * Logs the current user out and returns with no one logged in.
     */
    private function setQuestionStatus($question, $statusID) {
        $this->logOut();
        $this->logIn('slatest');
        $question->StatusWithType->Status->ID = $statusID;
        $question->save();
        $this->logOut();
        $this->clearCache($question->SocialPermissions);
    }

    /**
     * Sets the status of the given comment to the given value and clears the permission cache.
     * Logs the current user out and returns with no one logged in.
     */
    private function setCommentStatus($comment, $statusID) {
        $this->logOut();
        $this->logIn('slatest');
        $comment->StatusWithType->Status->ID = $statusID;
        $comment->save();
        $this->logOut();
        $this->clearCache($comment->SocialPermissions);
    }

    function testStatus(){
        $mockComment = $this->getMockedComment(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_COMMENT_ACTIVE))));
        $decorator = new Decorators\SocialCommentPermissions($mockComment);

        $this->assertTrue($decorator->isActive());
        $this->assertFalse($decorator->isPending());
        $this->assertFalse($decorator->isSuspended());
        $this->assertFalse($decorator->isDeleted());

        $this->clearCache($decorator);
        $mockComment->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_COMMENT_PENDING;
        $this->assertFalse($decorator->isActive());
        $this->assertTrue($decorator->isPending());
        $this->assertFalse($decorator->isSuspended());
        $this->assertFalse($decorator->isDeleted());

        $this->clearCache($decorator);
        $mockComment->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_COMMENT_SUSPENDED;
        $this->assertFalse($decorator->isActive());
        $this->assertFalse($decorator->isPending());
        $this->assertTrue($decorator->isSuspended());
        $this->assertFalse($decorator->isDeleted());

        $this->clearCache($decorator);
        $mockComment->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_COMMENT_DELETED;
        $this->assertFalse($decorator->isActive());
        $this->assertFalse($decorator->isPending());
        $this->assertFalse($decorator->isSuspended());
        $this->assertTrue($decorator->isDeleted());
    }

    function testIsAuthor(){
        list($question, $comment) = $this->createQuestionAndCommentAsUser('useractive1');

        $this->assertFalse($comment->SocialPermissions->isAuthor());

        $this->clearCache($comment->SocialPermissions);
        $this->logIn('useractive2');
        $this->assertFalse($comment->SocialPermissions->isAuthor());

        $this->clearCache($comment->SocialPermissions);
        $this->logOut();
        $this->logIn('useractive1');
        $this->assertTrue($comment->SocialPermissions->isAuthor());
    }

    function testCanRead() {
        list($question, $comment) = $this->createQuestionAndCommentAsUser('useractive1');

        // anonymous user should be able to read
        $this->assertTrue($comment->SocialPermissions->canRead());

        // so should non-author
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canRead());
        $this->logOut();
        $this->clearCache($comment->SocialPermissions);

        // User status doesn't matter for read
        $this->logIn('usersuspended');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canRead());
        $this->logOut();
        $this->clearCache($comment->SocialPermissions);

        // Can't read comments on non-active questions
        $this->setQuestionStatus($question, 32); // pending
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canRead());

        $this->setQuestionStatus($question, 30); // suspended
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canRead());

        $this->setQuestionStatus($question, 31); // deleted
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canRead());

        $this->setQuestionStatus($question, 29); // active

        // Can't read non-active comments
        $this->setCommentStatus($comment, 36); // pending
        $this->assertFalse($comment->SocialPermissions->canRead());

        $this->setCommentStatus($comment, 34); // suspended
        $this->assertFalse($comment->SocialPermissions->canRead());

        $this->setCommentStatus($comment, 35); // deleted
        $this->assertFalse($comment->SocialPermissions->canRead());

        // Can read suspended/pending comments as moderator
        $this->setCommentStatus($comment, 36); // pending
        $this->logIn('useradmin');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canRead());

        $this->setCommentStatus($comment, 34); // suspended
        $this->logIn('useradmin');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canRead());

        // Should be able to read pending if they're the author
        $this->setCommentStatus($comment, 36); // pending
        $this->logIn('useractive1');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canRead());

        $this->setCommentStatus($comment, 33); // active

        // Should be able to read comments on locked questions
        $this->logOut();
        $this->logIn('useradmin');
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->update($question->ID, array(
            'CommunityQuestion.Attributes.ContentLocked' => (object) array('value' => true)
        )))->result;
        $this->logOut();
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canRead());

        // Should not be able to read comments on suspended questions
        $this->setQuestionStatus($question, 30); // suspended
        $this->logIn('modactive1');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canRead());
        $this->logout();

        $this->destroyObject($comment);
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    function testCanCreate() {
        list($question, ) = $this->createQuestionAndCommentAsUser('useractive1');
        $comment = new Connect\CommunityComment();
        $comment->CommunityQuestion = $question;
        $decorator = new Decorators\SocialCommentPermissions($comment);

        // Can't create without being logged in
        $this->assertFalse($decorator->canCreate());

        // Active user can create
        $this->logIn('useractive1');
        $this->clearCache($decorator);
        $this->assertTrue($decorator->canCreate());
        $this->logOut();

        // Can't create as non-active user
        $this->logIn('usersuspended');
        $this->clearCache($decorator);
        $this->assertFalse($decorator->canCreate());
    }

    function testCanUpdate() {
        list($question, $comment) = $this->createQuestionAndCommentAsUser('useractive1');

        //Can't edit without being logged in
        $this->assertFalse($comment->SocialPermissions->canUpdate());

        // non-author can't edit
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canUpdate());

        // author can edit
        $this->logIn('useractive1');
        $this->clearCache($comment->SocialPermissions);
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canUpdate());

        // Can't edit comments on non-active questions
        $this->setQuestionStatus($question, 32); // pending
        $this->logIn('useractive1');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canUpdate());

        $this->setQuestionStatus($question, 30); // suspended
        $this->logIn('useractive1');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canUpdate());

        $this->setQuestionStatus($question, 31); // deleted
        $this->logIn('useractive1');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canUpdate());

        $this->setQuestionStatus($question, 29); // active

        // Can't edit non-active comments except pending
        $this->setCommentStatus($comment, 36); // pending
        $this->logIn('useractive1');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canUpdate());

        $this->setCommentStatus($comment, 34); // suspended
        $this->logIn('useractive1');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canUpdate());

        $this->setCommentStatus($comment, 35); // deleted
        $this->logIn('useractive1');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canUpdate());

        $this->setCommentStatus($comment, 33); // active

        //Can't edit as non-active user
        $this->logIn('usersuspended');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canUpdate());

        // Author can't edit comments on locked questions
        $this->logIn('useradmin');
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->update($question->ID, array(
            'CommunityQuestion.Attributes.ContentLocked' => (object) array('value' => true)
        )))->result;
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canUpdate());

        // Can edit locked question comments as moderator
        $this->logIn('modactive1');
        $this->clearCache($question->SocialPermissions);
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canUpdate());
    }

    function testCanUpdateWithTabularData() {
        list($question, $comment) = $this->createQuestionAndCommentAsUser('useractive1');

        $tabularComment = $this->assertResponseObject($this->CI->model('CommunityComment')->getTabular($comment->ID))->result;
        $decorator = new Decorators\SocialCommentPermissions($tabularComment);

        // author can edit
        $this->logIn('useractive1');
        $this->assertTrue($decorator->canUpdate());
        $this->logOut();

        // non-author can't edit
        $this->logIn('useractive2');
        $this->clearCache($decorator);
        $this->assertFalse($decorator->canUpdate());

        $this->destroyObject($comment);
        $this->destroyObject($question);
        Connect\ConnectAPI::commit();
    }

    function testCanFlag() {
        list($question, $comment) = $this->createQuestionAndCommentAsUser('useractive1');

        // Can't flag without being logged in
        $this->assertFalse($comment->SocialPermissions->canFlag());

        // Author can't flag own comment
        $this->logIn('useractive1');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canFlag());

        // logged in user should be able to flag twice, the second one proves that they
        // can overwrite the previous flag
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canFlag());
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canFlag());

        // Can't flag comments on non-active questions
        $this->setQuestionStatus($question, 32); // pending
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canFlag());

        $this->setQuestionStatus($question, 30); // suspended
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canFlag());

        $this->setQuestionStatus($question, 31); // deleted
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canFlag());

        $this->setQuestionStatus($question, 29); // active

        // Can't flag non-active comments
        $this->setCommentStatus($comment, 36); // pending
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canFlag());

        $this->setCommentStatus($comment, 34); // suspended
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canFlag());

        $this->setCommentStatus($comment, 35); // deleted
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canFlag());

        $this->setCommentStatus($comment, 33); // active

        // Can't flag as non-active user
        $this->logIn('usersuspended');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canFlag());

        // Can flag comments on a locked question as a moderator
        $this->logIn('slatest');
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->update($question->ID, array(
            'CommunityQuestion.Attributes.ContentLocked' => (object) array('value' => true)
        )))->result;
        $this->logIn('useradmin');
        $this->clearCache($question->SocialPermissions);
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canFlag());

        // Regular user can't flag comments on locked questions
        $this->logIn('useractive2');
        $this->clearCache($question->SocialPermissions);
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canFlag());
    }

    function testCanDeleteFlag() {
        list($question, $comment) = $this->createQuestionAndCommentAsUser('useractive1');
        // flag the comment as a different user
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $flag = $this->assertResponseObject($this->CI->model('CommunityComment')->flagComment($comment, 1))->result;
        $this->logOut();
        $this->clearCache($comment->SocialPermissions);

        // Can't delete delete flag without being logged in
        $this->assertFalse($comment->SocialPermissions->canDeleteFlag($flag));

        // Need to pass a valid flag
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canDeleteFlag($null));
        $this->assertFalse($comment->SocialPermissions->canDeleteFlag('shibby'));

        //  Comment author can't delete flag on own comment
        $this->logIn('useractive1');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteFlag($flag));

        // Flag author can delete their own flag
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canDeleteFlag($flag));

        // Can't delete flags on non-active questions
        $this->setQuestionStatus($question, 32); // pending
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteFlag($flag));

        $this->setQuestionStatus($question, 30); // suspended
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteFlag($flag));

        $this->setQuestionStatus($question, 31); // deleted
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteFlag($flag));

        $this->setQuestionStatus($question, 29); // active

        // Can't delete flags on non-active comments
        $this->setCommentStatus($comment, 36); // pending
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteFlag($flag));

        $this->setCommentStatus($comment, 34); // suspended
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteFlag($flag));

        $this->setCommentStatus($comment, 35); // deleted
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteFlag($flag));

        $this->setCommentStatus($comment, 33); // active

        // Can't delete flags as non-active user
        $this->logIn('usersuspended');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteFlag($flag));

        // Can delete flags on a locked question as a moderator
        $this->logIn('slatest');
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->update($question->ID, array(
            'CommunityQuestion.Attributes.ContentLocked' => (object) array('value' => true)
        )))->result;
        $this->logIn('useradmin');
        $this->clearCache($question->SocialPermissions);
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canDeleteFlag($flag));

        // Regular user can't delete flags on locked questions
        $this->logIn('useractive2');
        $this->clearCache($question->SocialPermissions);
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteFlag($flag));
    }

    function testCanRate() {
        list($question, $comment) = $this->createQuestionAndCommentAsUser('useractive1');

        // Can't rate without being logged in
        $this->assertFalse($comment->SocialPermissions->canRate());

        // Author can't rate own comment
        $this->logIn('useractive1');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canRate());

        // logged in user should be able to rate twice, the second one proves that they
        // can overwrite the previous rate
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canRate());
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canRate());

        // Can't rate comments on non-active questions
        $this->setQuestionStatus($question, 32); // pending
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canRate());

        $this->setQuestionStatus($question, 30); // suspended
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canRate());

        $this->setQuestionStatus($question, 31); // deleted
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canRate());

        $this->setQuestionStatus($question, 29); // active

        // Can't rate non-active comments
        $this->setCommentStatus($comment, 36); // pending
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canRate());

        $this->setCommentStatus($comment, 34); // suspended
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canRate());

        $this->setCommentStatus($comment, 35); // deleted
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canRate());

        $this->setCommentStatus($comment, 33); // active

        // Can't rate as non-active user
        $this->logIn('usersuspended');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canRate());

        // Can rate comments on a locked question as a moderator
        $this->logIn('slatest');
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->update($question->ID, array(
            'CommunityQuestion.Attributes.ContentLocked' => (object) array('value' => true)
        )))->result;
        $this->logIn('useradmin');
        $this->clearCache($question->SocialPermissions);
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canRate());

        // Regular user can't rate comments on locked questions
        $this->logIn('useractive2');
        $this->clearCache($question->SocialPermissions);
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canRate());
    }

    function testCanDeleteRating() {
        list($question, $comment) = $this->createQuestionAndCommentAsUser('useractive1');
        // rate the comment as a different user
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $rating = $this->assertResponseObject($this->CI->model('CommunityComment')->rateComment($comment, 50))->result;
        $this->logOut();
        $this->clearCache($comment->SocialPermissions);

        // Can't delete delete rating without being logged in
        $this->assertFalse($comment->SocialPermissions->canDeleteRating($rating));

        // Need to pass a valid rating
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteRating($null));
        $this->assertFalse($comment->SocialPermissions->canDeleteRating('shibby'));

        //  Comment author can't delete rating on own comment
        $this->logIn('useractive1');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteRating($rating));

        // Rating author can delete their own rating
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canDeleteRating($rating));

        // Can't delete ratings on non-active questions
        $this->setQuestionStatus($question, 32); // pending
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteRating($rating));

        $this->setQuestionStatus($question, 30); // suspended
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteRating($rating));

        $this->setQuestionStatus($question, 31); // deleted
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteRating($rating));

        $this->setQuestionStatus($question, 29); // active

        // Can't delete ratings on non-active comments
        $this->setCommentStatus($comment, 36); // pending
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteRating($rating));

        $this->setCommentStatus($comment, 34); // suspended
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteRating($rating));

        $this->setCommentStatus($comment, 35); // deleted
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteRating($rating));

        $this->setCommentStatus($comment, 33); // active

        // Can't delete ratings as non-active user
        $this->logIn('usersuspended');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteRating($rating));

        // Can delete ratings on a locked question as a moderator
        $this->logIn('slatest');
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->update($question->ID, array(
            'CommunityQuestion.Attributes.ContentLocked' => (object) array('value' => true)
        )))->result;
        $this->logIn('useradmin');
        $this->clearCache($question->SocialPermissions);
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canDeleteRating($rating));

        // Regular user can't delete ratings on locked questions
        $this->logIn('useractive2');
        $this->clearCache($question->SocialPermissions);
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDeleteRating($rating));
    }

    function testCanReply() {
        $this->logIn('useractive2');
        $mockComment = $this->getMockedComment(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_COMMENT_ACTIVE))));
        $decorator = new Decorators\SocialCommentPermissions($mockComment);
        $this->setMockQuestionOnDecorator($decorator, array('canComment' => true));

        $this->assertTrue($decorator->canReply());

        //Can't reply to non active comments
        $this->clearCache($decorator);
        $mockComment->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_COMMENT_PENDING;
        $this->assertFalse($decorator->canReply());

        $this->clearCache($decorator);
        $mockComment->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_COMMENT_SUSPENDED;
        $this->assertFalse($decorator->canReply());

        $this->clearCache($decorator);
        $mockComment->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_COMMENT_DELETED;
        $this->assertFalse($decorator->canReply());

        //Can't reply when question->canComment returns false
        $this->clearCache($decorator);
        $mockComment->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_COMMENT_ACTIVE;
        $this->setMockQuestionOnDecorator($decorator, array('canComment' => false));
        $this->assertFalse($decorator->canReply());

        //Can't reply when parent ID is already set
        $this->clearCache($decorator);
        $mockComment->Parent = (object)array('ID' => 1);
        $this->setMockQuestionOnDecorator($decorator, array('canComment' => true));
        $this->assertFalse($decorator->canReply());

        $this->logOut();

        //Non-active users can't reply
        $this->logIn('usersuspended');
        $mockComment = $this->getMockedComment(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_COMMENT_ACTIVE))));
        $decorator = new Decorators\SocialCommentPermissions($mockComment);
        $this->setMockQuestionOnDecorator($decorator, array('canComment' => true));
        $this->assertFalse($decorator->canReply());
        $this->logOut();
    }

    function testIsQuestionActive() {
        list($question, $comment) = $this->createQuestionAndCommentAsUser('useractive1');
        $this->assertTrue($comment->SocialPermissions->isQuestionActive());

        list($question, $comment) = $this->createQuestionAndCommentAsUser('useractive1', 'Deleted');
        $this->assertFalse($comment->SocialPermissions->isQuestionActive());

        list($question, $comment) = $this->createQuestionAndCommentAsUser('useractive1', 'Suspended');
        $this->assertFalse($comment->SocialPermissions->isQuestionActive());

        list($question, $comment) = $this->createQuestionAndCommentAsUser('useractive1', 'Pending');
        $this->assertFalse($comment->SocialPermissions->isQuestionActive());
    }

    function testSetQuestionObject() {
        $mockComment = $this->getMockedComment(array('CommunityQuestion' => (object)array('ID' => 10), 'CreatedByCommunityUser' => (object)array('ID' => 5)));
        $decorator = new Decorators\SocialCommentPermissions($mockComment);

        $method = new \ReflectionMethod($decorator, 'setQuestionObject');
        $socialQuestion = new \ReflectionProperty($decorator, 'socialQuestion');
        $commentAuthor = new \ReflectionProperty($decorator, 'commentAuthorID');
        $method->setAccessible(true);
        $socialQuestion->setAccessible(true);
        $commentAuthor->setAccessible(true);

        $method->invoke($decorator);
        $this->assertConnectObject($socialQuestion->getValue($decorator), 'CommunityQuestion');
        $this->assertIdentical(10, $socialQuestion->getValue($decorator)->ID);
        $this->assertIdentical(5, $commentAuthor->getValue($decorator));
    }

    function testCreateEmptyObjectWithAttachedComment() {
        $mockComment = $this->getMockedComment(array());
        $decorator = new Decorators\SocialCommentPermissions($mockComment);

        $method = new \ReflectionMethod($decorator, 'createEmptyObjectWithAttachedComment');
        $method->setAccessible(true);

        $result = $method->invoke($decorator, 'Incident');
        $this->assertConnectObject($result, 'Incident');
        $this->assertConnectObject($result->CommunityComment, 'CommunityComment');
        $this->assertNull($result->CommunityComment->CreatedByCommunityUser);
        $this->assertNull($result->CommunityComment->CommunityQuestion);
        $this->assertNull($result->CreatedByCommunityUser);

        $socialQuestion = new \ReflectionProperty($decorator, 'socialQuestion');
        $commentAuthor = new \ReflectionProperty($decorator, 'commentAuthorID');
        $socialQuestion->setAccessible(true);
        $commentAuthor->setAccessible(true);
        $socialQuestion->setValue($decorator, new Connect\CommunityQuestion);
        $commentAuthor->setValue($decorator, 110); // mzoeller

        $this->logIn('useractive1');
        $result = $method->invoke($decorator, 'Contact');
        $this->assertConnectObject($result, 'Contact');
        $this->assertConnectObject($result->CommunityComment, 'CommunityComment');
        $this->assertConnectObject($result->CommunityComment->CreatedByCommunityUser, 'CommunityUser');
        $this->assertIdentical($result->CommunityComment->CreatedByCommunityUser->ID, 110);
        $this->assertConnectObject($result->CommunityComment->CommunityQuestion, 'CommunityQuestion');
        $this->assertConnectObject($result->CreatedByCommunityUser, 'CommunityUser');
    }

    function testCanUpdateStatus() {
        list($question, $comment) = $this->createQuestionAndCommentAsUser('useractive1');

        // Can't update status without being logged in
        $this->assertFalse($comment->SocialPermissions->canUpdateStatus());

        // non-author can't update status
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canUpdateStatus());

        // author can't update status either
        $this->logIn('useractive1');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canUpdateStatus());

        // moderator can update status for all comment statuses except deleted
        $this->logIn('useradmin');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canUpdateStatus());

        $this->setCommentStatus($comment, 36); // pending
        $this->logIn('useradmin');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canUpdateStatus());

        $this->setCommentStatus($comment, 34); // suspended
        $this->logIn('useradmin');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canUpdateStatus());

        $this->setCommentStatus($comment, 35); // deleted
        $this->logIn('useradmin');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canUpdateStatus());

        $this->setCommentStatus($comment, 33); // active

        $this->logIn('userupdateonly');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canUpdateStatus());

        $this->logIn('userdeletenoupdatestatus');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canUpdateStatus());

        // Non active moderators shouldn't be allowed to change status
        $this->logIn('modpending');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canUpdateStatus());

        $this->logIn('modsuspended');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canUpdateStatus());

        $this->logIn('moddeleted');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canUpdateStatus());

        $this->logIn('modarchive');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canUpdateStatus());
    }

    function testCanDelete() {
        list($question, $comment) = $this->createQuestionAndCommentAsUser('useractive1');

        //Can't delete without being logged in
        $this->assertFalse($comment->SocialPermissions->canDelete());

        // non-author can't delete
        $this->logIn('useractive2');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDelete());

        // author can delete
        $this->logIn('useractive1');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canDelete());

        // moderator can delete comments that are not already deleted
        $this->logIn('useradmin');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canDelete());

        $this->setCommentStatus($comment, 36); // pending
        $this->logIn('useradmin');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canDelete());

        $this->setCommentStatus($comment, 34); // suspended
        $this->logIn('useradmin');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canDelete());

        $this->setCommentStatus($comment, 35); // deleted
        $this->logIn('useradmin');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDelete());

        $this->setCommentStatus($comment, 33); // active

        $this->logIn('userupdateonly');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDelete());

        $this->logIn('userdeletenoupdatestatus');
        $this->clearCache($comment->SocialPermissions);
        $this->assertTrue($comment->SocialPermissions->canDelete());

        // Non active moderators shouldn't be allowed to delete
        $this->logIn('modpending');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDelete());

        $this->logIn('modsuspended');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDelete());

        $this->logIn('moddeleted');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDelete());

        $this->logIn('modarchive');
        $this->clearCache($comment->SocialPermissions);
        $this->assertFalse($comment->SocialPermissions->canDelete());

        // non-active users, even when authors, can't delete
        list($question, $comment) = $this->createQuestionAndCommentAsUser('useractive1');

        $this->logIn('userpending');
        $comment = new Connect\CommunityComment();
        $comment->CreatedByCommunityUser = $this->CI->model('CommunityUser')->get()->result->ID;
        $comment->CommunityQuestion = $question;
        $comment->save();
        \RightNow\Libraries\Decorator::add($comment, array('class' => 'Permission/SocialCommentPermissions', 'property' => 'SocialPermissions'));
        $this->assertFalse($comment->SocialPermissions->canDelete());
        $this->destroyObject($comment);

        $this->logIn('usersuspended');
        $comment = new Connect\CommunityComment();
        $comment->CreatedByCommunityUser = $this->CI->model('CommunityUser')->get()->result->ID;
        $comment->CommunityQuestion = $question;
        $comment->save();
        \RightNow\Libraries\Decorator::add($comment, array('class' => 'Permission/SocialCommentPermissions', 'property' => 'SocialPermissions'));
        $this->assertFalse($comment->SocialPermissions->canDelete());
        $this->destroyObject($comment);
    }

    function testCanDeleteWithTabularQuery() {
        $this->logIn('modactive1');
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        )))->result;
        $comment1 = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
        )))->result;
        $roql = "SELECT
            c.ID,
            c.LookupName,
            c.CreatedTime,
            c.UpdatedTime,
            c.ParentCreatedByCommunityUser.ID AS 'CreatedByCommunityUser.ID',
            c.ParentCreatedByCommunityUser.DisplayName AS 'CreatedByCommunityUser.DisplayName',
            c.ParentCreatedByCommunityUser.AvatarURL AS 'CreatedByCommunityUser.AvatarURL',
            c.Body,
            c.BodyContentType,
            c.Parent AS 'Parent.ID',
            c.CommunityQuestion AS 'CommunityQuestion.ID',
            c.StatusWithType.Status AS 'StatusWithType.Status.ID',
            c.StatusWithType.StatusType AS 'StatusWithType.StatusType.ID',
            c.Type,
            c.Parent.level1,
            c.Parent.level2,
            c.Parent.level3,
            c.Parent.level4,
            c.ContentRatingSummaries.NegativeVoteCount,
            c.ContentRatingSummaries.PositiveVoteCount,
            c.ContentRatingSummaries.RatingTotal,
            c.ContentRatingSummaries.RatingWeightedCount,
            c.ContentRatingSummaries.RatingWeightedTotal
        FROM CommunityComment c
        WHERE c.CommunityQuestion.ID = {$question->ID}";
        $query = ConnectTabular::query($roql, false);
        $decoratedTabularComments = $query->getCollection(array('class' => 'Permission/SocialCommentPermissions', 'property' => 'SocialPermissions'));
        $comment = $decoratedTabularComments[0];

        // decorator can handle tabular data automatically
        $this->assertTrue($comment->SocialPermissions->canDelete());

        $this->destroyObject($comment1);
        $this->destroyObject($question);
    }

    function testCanUpdateStatusWithTabularQuery() {
        $this->logIn('modactive1');
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'bananas ' . __FUNCTION__)
        )))->result;
        $comment1 = $this->assertResponseObject($this->CI->model('CommunityComment')->create(array(
            'CommunityComment.CommunityQuestion' => (object) array('value' => $question->ID),
        )))->result;
        $roql = "SELECT
            c.ID,
            c.LookupName,
            c.CreatedTime,
            c.UpdatedTime,
            c.ParentCreatedByCommunityUser.ID AS 'CreatedByCommunityUser.ID',
            c.ParentCreatedByCommunityUser.DisplayName AS 'CreatedByCommunityUser.DisplayName',
            c.ParentCreatedByCommunityUser.AvatarURL AS 'CreatedByCommunityUser.AvatarURL',
            c.Body,
            c.BodyContentType,
            c.Parent AS 'Parent.ID',
            c.CommunityQuestion AS 'CommunityQuestion.ID',
            c.StatusWithType.Status AS 'StatusWithType.Status.ID',
            c.StatusWithType.StatusType AS 'StatusWithType.StatusType.ID',
            c.Type,
            c.Parent.level1,
            c.Parent.level2,
            c.Parent.level3,
            c.Parent.level4,
            c.ContentRatingSummaries.NegativeVoteCount,
            c.ContentRatingSummaries.PositiveVoteCount,
            c.ContentRatingSummaries.RatingTotal,
            c.ContentRatingSummaries.RatingWeightedCount,
            c.ContentRatingSummaries.RatingWeightedTotal
        FROM CommunityComment c
        WHERE c.CommunityQuestion.ID = {$question->ID}";
        $query = ConnectTabular::query($roql, false);
        $decoratedTabularComments = $query->getCollection(array('class' => 'Permission/SocialCommentPermissions', 'property' => 'SocialPermissions'));
        $comment = $decoratedTabularComments[0];

        // decorator can handle tabular data automatically
        $this->assertTrue($comment->SocialPermissions->canUpdateStatus());

        $this->destroyObject($comment1);
        $this->destroyObject($question);
    }

    function testGetCommentShell() {
        $mockComment = $this->getMockedComment(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_COMMENT_ACTIVE))));
        $decorator = new Decorators\SocialCommentPermissions($mockComment);
        $method = new \ReflectionMethod($decorator, 'getCommentShell');
        $method->setAccessible(true);
        $commentShell = $method->invoke($decorator, $mockComment);
        $this->assertIsA($commentShell, 'RightNow\Connect\v1_4\CommunityComment');
        $this->assertNull($commentShell->ID);
    }

    function testGetCommentShellWithNullAuthor() {
        list($fixtureInstance, $question, $comment) = $this->getFixtures(array(
            'QuestionActiveModActive',
            'CommentActiveModArchive',
        ));
        \RightNow\Api::test_sql_exec_direct('update sss_question_comments set created_by = NULL where sss_question_comment_id = ' . $comment->ID);
        Connect\ConnectAPI::commit();

        $result = $this->makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/getCommentShellWithNullAuthor/' . $comment->ID);
        $this->assertIdentical('', $result);
        $fixtureInstance->destroy();
    }

    function getCommentShellWithNullAuthor() {
        $commentID = (int) \RightNow\Utils\Text::getSubstringAfter(get_instance()->uri->uri_string(), __FUNCTION__ . '/');
        $mockComment = $this->getMockedComment(array('ID' => $commentID, 'StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_COMMENT_ACTIVE))));
        $decorator = new Decorators\SocialCommentPermissions($mockComment);
        $method = new \ReflectionMethod($decorator, 'getCommentShell');
        $method->setAccessible(true);

        $commentShell = $method->invoke($decorator, $mockComment);
        $this->assertIsA($commentShell, 'RightNow\Connect\v1_4\CommunityComment');
        $this->assertIdentical($commentID, $commentShell->ID);
    }

    function testPermissionCaching() {
        list($fixtureInstance, $question, $commentOne, $commentTwo) = $this->getFixtures(array(
            'QuestionActiveModActive',
            'CommentWithRepliesUserArchive',
            'CommentActiveModArchive',
        ));

        $this->logIn();

        // verify that 'can' is only called once per permission for comments associated to the same question
        $commentOne->SocialPermissions = new SocialCommentPermissionChild($commentOne);
        $commentTwo->SocialPermissions = new SocialCommentPermissionChild($commentTwo);
        $tabularCommentOne = $this->CI->model('CommunityComment')->getTabular($commentOne->ID)->result;
        $tabularCommentOne->SocialPermissions = new SocialCommentPermissionChild($tabularCommentOne);
        $tabularCommentTwo = $this->CI->model('CommunityComment')->getTabular($commentTwo->ID)->result;
        $tabularCommentTwo->SocialPermissions = new SocialCommentPermissionChild($tabularCommentTwo);

        $this->assertIdentical(0, SocialCommentPermissionChild::$canCalled);

        // make sure tabular comments don't bomb out
        $tabularCommentOne->SocialPermissions->canFlag();
        $this->assertIdentical(1, SocialCommentPermissionChild::$canCalled);
        $tabularCommentTwo->SocialPermissions->canFlag();
        $this->assertIdentical(1, SocialCommentPermissionChild::$canCalled);

        $tabularCommentTwo->SocialPermissions->canRate();
        $this->assertIdentical(2, SocialCommentPermissionChild::$canCalled);
        $tabularCommentOne->SocialPermissions->canRate();
        $this->assertIdentical(2, SocialCommentPermissionChild::$canCalled);

        // make Connect comments don't increment
        $commentOne->SocialPermissions->canFlag();
        $this->assertIdentical(2, SocialCommentPermissionChild::$canCalled);
        $commentTwo->SocialPermissions->canFlag();
        $this->assertIdentical(2, SocialCommentPermissionChild::$canCalled);

        $commentTwo->SocialPermissions->canRate();
        $this->assertIdentical(2, SocialCommentPermissionChild::$canCalled);
        $commentOne->SocialPermissions->canRate();
        $this->assertIdentical(2, SocialCommentPermissionChild::$canCalled);

        $fixtureInstance->destroy();

        // do it again and verify that 'can' is called b/c this is a new question
        list($fixtureInstance, $question, $commentOne) = $this->getFixtures(array(
            'QuestionWithProduct',
            'CommentActiveModActive',
        ));

        $commentOne->SocialPermissions = new SocialCommentPermissionChild($commentOne);

        $commentOne->SocialPermissions->canFlag();
        $this->assertIdentical(3, SocialCommentPermissionChild::$canCalled);

        $commentOne->SocialPermissions->canRate();
        $this->assertIdentical(4, SocialCommentPermissionChild::$canCalled);

        $fixtureInstance->destroy();

        $this->logOut();
    }
}

class MockedSocialCommentForCommentTest extends Connect\RNObject{
    private $permissionResults = array();

    static function &getMetadata(){
        return (object)array('COM_type' => 'CommunityComment');
    }

    function setResultForPermission($permissionID, $returnValue){
        $this->permissionResults[$permissionID] = $returnValue;
    }

    function clearPermissions(){
        $this->permissionResults = array();
    }

    function hasPermission($permission){
        return $this->permissionResults[$permission->ID] ?: false;
    }
}

class MockSocialQuestionForCommentText extends Connect\RNObject{
    function __construct($methodResponses){
        $this->SocialPermissions = new MockQuestionPermissionsForCommentTest($methodResponses);
    }
}

class MockQuestionPermissionsForCommentTest{
    function __construct(array $methodResponses){
        $this->methodResponses = $methodResponses;
    }
    function __call($method, $arguments){
        return $this->methodResponses[$method] ?: false;
    }
}

class MockUserPermissionsForCommentTest{

    function __construct($isUserActive){
        $this->isUserActive = $isUserActive;
    }
    function isActive(){
        return $this->isUserActive;
    }
}

class SocialCommentPermissionChild extends Decorators\SocialCommentPermissions{
    public static $canCalled = 0;

    protected function can($permission, $alternateObject = null){
        self::$canCalled++;
        return parent::can($permission, $alternateObject);
    }
}
